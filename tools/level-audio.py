"""Even out a narration's volume, and check it.

    python3 tools/level-audio.py assets/audio/<slug>.mp3 [...]      level in place
    python3 tools/level-audio.py <in.mp3|wav> --out <out.mp3>       level to a new file
    python3 tools/level-audio.py --check assets/audio/<slug>.mp3    measure only; exit 1 if off

Why: ElevenLabs returns each chunk at its own level and the level drifts inside a
chunk too, and nothing used to correct it. On 12 Oct 2026 the eleven episodes ranged
from -38 to -17.5 LUFS (a 20 dB spread between episodes), and inside the bank-vs-broker
episode the voice went from about -42 to -18 and back across the seam at 2:15.

How: two passes of ffmpeg's loudnorm in its dynamic mode (a slow, loudness-based
leveller with 3-second windows, so it rides the drift without pumping on words), then a
measured linear pass to the target: -19 LUFS integrated, true peak at most -1.5 dBTP.
-19 LUFS is the usual podcast target for a mono file (it plays as loud as a -16 LUFS
stereo one, the figure Apple quotes). Output is 64 kbps mono 44.1 kHz MP3, as narrate.py
writes; the duration does not change.
"""
import argparse, json, os, re, shutil, subprocess, sys, tempfile

TARGET_I, TARGET_TP = -19.0, -1.5
# What --check accepts: integrated within 1 LU of the target, peaks below -1 dBTP, and no
# stretch of 10 seconds more than SPREAD dB away from the episode's middle level.
TOL_I, MAX_TP, SPREAD = 1.0, -1.0, 4.0


def ff(*args):
    return subprocess.run(["ffmpeg", "-hide_banner", "-nostats", *args], capture_output=True, text=True)


def measure(path):
    """Integrated loudness, true peak, and the 10-second short-term levels (speech only)."""
    r = ff("-i", path, "-af", "ebur128=peak=true:metadata=1,ametadata=print:key=lavfi.r128.S:file=-",
           "-f", "null", "-")
    out = r.stdout + r.stderr
    summ = out[out.rfind("Summary:"):]
    i = float(re.search(r"I:\s+(-?[\d.]+) LUFS", summ).group(1))
    tp = float(re.search(r"Peak:\s+(-?[\d.inf]+) dBFS", summ).group(1))
    buckets, t = {}, 0.0
    for line in out.splitlines():
        m = re.search(r"pts_time:([\d.]+)", line)
        if m:
            t = float(m.group(1))
        m = re.search(r"lavfi\.r128\.S=(-?[\d.inf]+)", line)
        if m and t > 3 and m.group(1) not in ("-inf", "inf"):
            buckets.setdefault(int(t // 10), []).append(float(m.group(1)))
    levels = [sum(v) / len(v) for _, v in sorted(buckets.items())]
    # A stretch that is mostly pause measures low without being quiet speech; skip those.
    levels = [x for x in levels if x > i - 15]
    return i, tp, levels


def spread(i, levels):
    return max((abs(x - i) for x in levels), default=0.0)


def level(src, dst):
    tmp = tempfile.mkdtemp(prefix="level-")
    try:
        ride = os.path.join(tmp, "ride.wav")
        dyn = "loudnorm=I=-21:TP=-3:LRA=5,aresample=44100"
        r = ff("-y", "-i", src, "-af", f"{dyn},{dyn}", "-ac", "1", "-c:a", "pcm_s16le", ride)
        if r.returncode:
            sys.exit(r.stderr)
        r = ff("-i", ride, "-af", f"loudnorm=I={TARGET_I}:TP={TARGET_TP}:LRA=11:print_format=json", "-f", "null", "-")
        m = json.loads(r.stderr[r.stderr.rfind("{"):r.stderr.rfind("}") + 1])
        norm = (f"loudnorm=I={TARGET_I}:TP={TARGET_TP}:LRA=11:linear=true:measured_I={m['input_i']}"
                f":measured_TP={m['input_tp']}:measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}"
                f":offset={m['target_offset']},aresample=44100")
        out = os.path.join(tmp, "out.mp3")
        # MP3 encoding overshoots peaks by up to ~2.5 dB (two episodes came out above 0 dBTP
        # without a limiter, and one at 0.0 with a 5 ms one, 13 Oct 2026). Hold sample peaks
        # down before encoding, and lower the ceiling until the encoded file passes.
        for limit in (0.708, 0.6, 0.5):
            final = f"{norm},alimiter=limit={limit}:level=false:attack=1:release=50"
            r = ff("-y", "-i", ride, "-af", final, "-codec:a", "libmp3lame", "-b:a", "64k", "-ac", "1", "-ar", "44100", out)
            if r.returncode:
                sys.exit(r.stderr)
            if measure(out)[1] <= MAX_TP - 0.2:
                break
        shutil.move(out, dst)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def duration(path):
    return float(subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", path],
                                capture_output=True, text=True).stdout)


def report(path):
    i, tp, levels = measure(path)
    s = spread(i, levels)
    ok = abs(i - TARGET_I) <= TOL_I and tp <= MAX_TP and s <= SPREAD
    print(f"{'ok  ' if ok else 'OFF '} {os.path.basename(path)}: {i:.1f} LUFS, peak {tp:.1f} dB, "
          f"10-s stretches within {s:.1f} dB of it ({min(levels, default=0):.0f} to {max(levels, default=0):.0f})")
    return ok


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("files", nargs="+")
    p.add_argument("--out", help="write here instead of in place (one input only)")
    p.add_argument("--check", action="store_true", help="measure only")
    a = p.parse_args()
    if a.check:
        sys.exit(0 if all([report(f) for f in a.files]) else 1)
    if a.out and len(a.files) != 1:
        sys.exit("--out takes one input")
    bad = 0
    for f in a.files:
        dst = a.out or f
        before = duration(f)
        level(f, dst)
        if abs(duration(dst) - before) > 0.5:
            print(f"OFF  {dst}: length changed {before:.1f}s -> {duration(dst):.1f}s"); bad += 1
        bad += not report(dst)
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
