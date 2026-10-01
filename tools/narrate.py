"""Narrate a blog article in Erika's cloned voice.

    ELEVENLABS_API_KEY=... python3 tools/narrate.py <slug>
    python3 tools/narrate.py <slug> --dry-run            # show the script and chunk plan only
    python3 tools/narrate.py <slug> --numbered-label Reason   # read "1. Foo" as "Reason 1. Foo"

Writes assets/audio/<slug>.mp3 and prints the measured duration to put in the
post's `audio_secs`. The key comes from the environment and is never committed;
ask the site owner for it each session.

How it is built, and why:
  * The script is the article itself: title, then body, with pictures dropped,
    headings read as sentences and list items as their own sentences (without a
    second full stop after a "?").
  * ElevenLabs is unreliable on very long requests, so the text is split into
    chunks of ~2,300 characters on paragraph boundaries. Each request is given the
    end of the previous chunk and the start of the next (previous_text/next_text),
    which stops pitch and pace resetting audibly at every seam.
  * The parts are joined with ffmpeg and re-encoded to 64 kbps mono, to match the
    other narrations on the site (128 kbps doubles the file for no audible gain
    in speech).
  * The duration is measured from the finished file with the site's own
    mp3_duration(), never typed in.

Afterwards, run tools/stt-check.py on the result: numbers and near-homophones
("a valuation" / "evaluation") are where text-to-speech goes wrong.
"""
import argparse, html, json, os, re, shutil, ssl, subprocess, sys, tempfile, time, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VOICE = os.environ.get("ELEVENLABS_VOICE", "A0cqQbKSKNvw6IyjpQ5n")   # "Erika", professional clone
MODEL = os.environ.get("ELEVENLABS_MODEL", "eleven_multilingual_v2")
LIMIT = 2300


def post(slug):
    out = subprocess.run(["php", "-r", 'require "cms.php"; echo json_encode(blog_post($argv[1], true));', slug],
                         cwd=ROOT, capture_output=True, text=True, check=True).stdout
    p = json.loads(out)
    if not p:
        sys.exit(f"no post with slug {slug!r}")
    return p


def script(p, label):
    b = re.sub(r"\[\[img:[^\]]*\]\]", "", p["body"])

    def h2(m):
        t = re.sub(r"<[^>]+>", "", m.group(1)).strip()
        if label:
            t = re.sub(r"^(\d+)\.\s*", rf"{label} \1. ", t)
        return "\n\n" + (t if re.search(r"[.?!]$", t) else t + ".") + "\n\n"

    def li(m):
        t = re.sub(r"<[^>]+>", "", m.group(1)).strip()
        return "\n" + (t if re.search(r"[.?!]$", t) else t + ".") + "\n"

    b = re.sub(r"<h2>(.*?)</h2>", h2, b, flags=re.S)
    b = re.sub(r"<li>(.*?)</li>", li, b, flags=re.S)
    b = b.replace("</p>", "\n\n")
    b = html.unescape(re.sub(r"<[^>]+>", "", b))
    b = b.replace("—", " - ").replace("–", " - ")
    b = re.sub(r"[ \t]+", " ", b)
    b = re.sub(r"\n{3,}", "\n\n", b).strip()
    title = html.unescape(re.sub(r"<[^>]+>", "", p["title"]))
    return title + "\n\n" + b


def chunks(text):
    out, cur = [], ""
    for para in text.split("\n\n"):
        if cur and len(cur) + len(para) + 2 > LIMIT:
            out.append(cur.strip()); cur = ""
        cur += para + "\n\n"
    if cur.strip():
        out.append(cur.strip())
    return out


def tts(key, text, prev, nxt, out):
    ca = os.environ.get("CA_BUNDLE", "/root/.ccr/ca-bundle.crt")
    ctx = ssl.create_default_context(cafile=ca if os.path.exists(ca) else None)
    proxy = os.environ.get("HTTPS_PROXY") or os.environ.get("https_proxy")
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({"https": proxy} if proxy else {}),
                                         urllib.request.HTTPSHandler(context=ctx))
    body = {"text": text, "model_id": MODEL,
            "voice_settings": {"stability": 0.45, "similarity_boost": 0.8, "style": 0.0, "use_speaker_boost": True}}
    if prev: body["previous_text"] = prev[-600:]
    if nxt:  body["next_text"] = nxt[:600]
    req = urllib.request.Request(
        f"https://api.elevenlabs.io/v1/text-to-speech/{VOICE}?output_format=mp3_44100_128",
        data=json.dumps(body).encode(), method="POST",
        headers={"xi-api-key": key, "Content-Type": "application/json"})
    for attempt in range(4):
        try:
            with opener.open(req, timeout=300) as r:
                audio = r.read()
            if len(audio) < 10000:
                raise RuntimeError(f"short response ({len(audio)} bytes)")
            open(out, "wb").write(audio)
            return
        except Exception as e:
            detail = e.read()[:200].decode("utf-8", "replace") if hasattr(e, "read") else ""
            print(f"    attempt {attempt + 1} failed: {e} {detail}")
            if attempt == 3:
                raise
            time.sleep(4 * (attempt + 1))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("slug")
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--numbered-label", default="")
    ap.add_argument("--out")
    a = ap.parse_args()

    p = post(a.slug)
    text = script(p, a.numbered_label)
    parts = chunks(text)
    print(f"{len(text)} characters -> {len(parts)} chunks: {[len(c) for c in parts]}")
    if a.dry_run:
        print("\n----- script -----\n" + text)
        return

    key = os.environ.get("ELEVENLABS_API_KEY") or sys.exit("set ELEVENLABS_API_KEY")
    out = a.out or os.path.join("assets", "audio", f"{a.slug}.mp3")
    tmp = tempfile.mkdtemp(prefix="narrate-")
    try:
        files = []
        for i, c in enumerate(parts):
            f = os.path.join(tmp, f"part{i:02d}.mp3")
            tts(key, c, parts[i - 1] if i else "", parts[i + 1] if i + 1 < len(parts) else "", f)
            print(f"  part {i}: {len(c)} chars -> {os.path.getsize(f) >> 10} KB")
            files.append(f)
        lst = os.path.join(tmp, "list.txt")
        open(lst, "w").write("".join(f"file '{f}'\n" for f in files))
        raw = os.path.join(tmp, "raw.mp3")
        subprocess.run(["ffmpeg", "-v", "error", "-y", "-f", "concat", "-safe", "0", "-i", lst, "-c", "copy", raw], check=True)
        subprocess.run(["ffmpeg", "-v", "error", "-y", "-i", raw, "-codec:a", "libmp3lame", "-b:a", "64k",
                        "-ac", "1", "-ar", "44100", os.path.join(ROOT, out)], check=True)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    secs = subprocess.run(["php", "-r", 'require "cms.php"; echo mp3_duration($argv[1]);', out],
                          cwd=ROOT, capture_output=True, text=True, check=True).stdout.strip()
    print(f"wrote {out} ({os.path.getsize(os.path.join(ROOT, out)) >> 10} KB)")
    print(f"audio_secs => {secs}    (set this on the post in posts.php)")


if __name__ == "__main__":
    main()
