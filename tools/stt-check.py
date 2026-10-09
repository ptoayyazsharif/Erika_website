"""Listen back to a narration: transcribe the opening, chosen points and the ending.

    ELEVENLABS_API_KEY=... python3 tools/stt-check.py assets/audio/<slug>.mp3
    python3 tools/stt-check.py <file> --at 132 --at 264        # also check around the seams
    python3 tools/stt-check.py <file> --full                   # the whole episode in one call

Compare what comes back with the article. This is how the narration checks
caught "not a valuation" being heard as "not evaluation" — two phrases that are
nearly identical spoken and opposite in meaning — and confirmed dollar figures
are read correctly. Seams fall where tools/narrate.py's chunks meet; the chunk
durations it reports tell you where to look.
"""
import argparse, json, os, ssl, subprocess, sys, tempfile, urllib.request, uuid


def transcribe(key, path):
    ca = os.environ.get("CA_BUNDLE", "/root/.ccr/ca-bundle.crt")
    ctx = ssl.create_default_context(cafile=ca if os.path.exists(ca) else None)
    proxy = os.environ.get("HTTPS_PROXY") or os.environ.get("https_proxy")
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({"https": proxy} if proxy else {}),
                                         urllib.request.HTTPSHandler(context=ctx))
    b = uuid.uuid4().hex
    body = (f'--{b}\r\nContent-Disposition: form-data; name="model_id"\r\n\r\nscribe_v1\r\n'
            f'--{b}\r\nContent-Disposition: form-data; name="file"; filename="a.mp3"\r\n'
            f'Content-Type: audio/mpeg\r\n\r\n').encode() + open(path, "rb").read() + f"\r\n--{b}--\r\n".encode()
    req = urllib.request.Request("https://api.elevenlabs.io/v1/speech-to-text", data=body, method="POST",
                                 headers={"xi-api-key": key, "Content-Type": f"multipart/form-data; boundary={b}"})
    with opener.open(req, timeout=300) as r:
        return json.load(r).get("text", "").strip()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("mp3")
    ap.add_argument("--at", type=float, action="append", default=[], help="seconds; checks ~14s either side")
    ap.add_argument("--head", type=float, default=40)
    ap.add_argument("--tail", type=float, default=35)
    ap.add_argument("--full", action="store_true", help="transcribe the whole file in one call (a whole-episode listen-back)")
    a = ap.parse_args()
    key = os.environ.get("ELEVENLABS_API_KEY") or sys.exit("set ELEVENLABS_API_KEY")

    with tempfile.TemporaryDirectory() as tmp:
        if a.full:
            cuts = [("whole file", ["-i", a.mp3])]
        else:
            cuts = [("opening", ["-t", str(a.head), "-i", a.mp3])] if a.head > 0 else []
            cuts += [(f"around {t:.0f}s", ["-ss", str(max(0, t - 14)), "-t", "28", "-i", a.mp3]) for t in a.at]
            cuts += [("ending", ["-sseof", f"-{a.tail}", "-i", a.mp3])] if a.tail > 0 else []
        for name, args in cuts:
            out = os.path.join(tmp, "cut.mp3")
            subprocess.run(["ffmpeg", "-v", "error", "-y", *args, out], check=True)
            print(f"--- {name} ---\n{transcribe(key, out)}\n")


if __name__ == "__main__":
    main()
