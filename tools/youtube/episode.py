"""Turn a podcast episode into a YouTube video, with captions, chapters and a thumbnail.

    ELEVENLABS_API_KEY=... python3 tools/youtube/episode.py <slug>
    python3 tools/youtube/episode.py <slug> --episode 1 --note "Recorded September 29, 2026."
    python3 tools/youtube/episode.py <slug> --numbered-label Question   # as narrate.py was run

Writes, under exports/youtube/<slug>/ (git-ignored; the owner uploads):
  <slug>.mp4      1920x1080: the square episode art on the left, the Bold merlot panel
                  on the right with the captions burned into it, a gold progress line
  <slug>.en.srt   the same captions as a file, for YouTube's subtitle upload (its own
                  automatic captions write "Erica")
  chapters.txt    YouTube chapters from the article's headings (read-aloud episodes only)
  upload.md       title, description (with the chapters), tags and the settings to choose
  frame.png       the still frame, for a look before the long encode

The thumbnail comes from the cover template: node tools/covers/blog.js <slug> --youtube.

How the captions are made:
  * The words and their times come from ElevenLabs' transcriber, once per recording,
    and are kept in tools/youtube/timing/<slug>.json (committed), so the video can be
    rebuilt without a key. A re-recorded MP3 (different size) is transcribed again.
  * The transcriber mishears ("Erica", "Axon Realty", "not evaluation"), so its words
    are aligned with the text that was recorded — the podcast script, or the article as
    narrate.py reads it — and the text's own words are shown wherever the two agree.
  * Where they really differ, the recording wins: captions show what was said. The
    closing-costs episode (29 Sep) was recorded before its article was re-dated on
    13 Oct, so its captions keep the words it was recorded with. Every such passage is
    printed: read them before uploading.
"""
import argparse, difflib, hashlib, html, importlib.util, json, os, re, ssl, subprocess, sys, tempfile, urllib.request, uuid

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
HERE = os.path.dirname(os.path.abspath(__file__))
NODE_PATH = os.environ.get("NODE_PATH", "/opt/node22/lib/node_modules")
CUE_CHARS = 56          # about three lines of 66-px type in the 700-px caption column (readable on a phone)
FIXES = {"erica": "Erika", "erica's": "Erika's", "axon": "Axen"}

spec = importlib.util.spec_from_file_location("narrate", os.path.join(ROOT, "tools", "narrate.py"))
narrate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(narrate)


def transcribe_words(key, path):
    ca = os.environ.get("CA_BUNDLE", "/root/.ccr/ca-bundle.crt")
    ctx = ssl.create_default_context(cafile=ca if os.path.exists(ca) else None)
    proxy = os.environ.get("HTTPS_PROXY") or os.environ.get("https_proxy")
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({"https": proxy} if proxy else {}),
                                         urllib.request.HTTPSHandler(context=ctx))
    b = uuid.uuid4().hex
    field = lambda k, v: f'--{b}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'
    body = (field("model_id", "scribe_v1") + field("timestamps_granularity", "word") + field("tag_audio_events", "false")
            + f'--{b}\r\nContent-Disposition: form-data; name="file"; filename="a.mp3"\r\nContent-Type: audio/mpeg\r\n\r\n'
            ).encode() + open(path, "rb").read() + f"\r\n--{b}--\r\n".encode()
    req = urllib.request.Request("https://api.elevenlabs.io/v1/speech-to-text", data=body, method="POST",
                                 headers={"xi-api-key": key, "Content-Type": f"multipart/form-data; boundary={b}"})
    with opener.open(req, timeout=600) as r:
        words = json.load(r).get("words", [])
    return [[w["text"], round(w["start"], 3), round(w["end"], 3)] for w in words if w.get("type") == "word"]


def timings(slug, mp3):
    f = os.path.join(HERE, "timing", f"{slug}.json")
    digest = hashlib.sha256(open(mp3, "rb").read()).hexdigest()
    if os.path.exists(f):
        d = json.load(open(f))
        if d.get("sha256") == digest:
            return d["words"]
        print(f"  {slug}.mp3 has changed since it was transcribed: transcribing again")
    key = os.environ.get("ELEVENLABS_API_KEY") or sys.exit("no saved timings for this recording: set ELEVENLABS_API_KEY")
    words = transcribe_words(key, mp3)
    os.makedirs(os.path.dirname(f), exist_ok=True)
    json.dump({"mp3": os.path.relpath(mp3, ROOT), "sha256": digest, "words": words}, open(f, "w"), indent=0)
    return words


SMALL = {w: i for i, w in enumerate("zero one two three four five six seven eight nine ten eleven twelve thirteen "
                                     "fourteen fifteen sixteen seventeen eighteen nineteen".split())}
TENS = {w: 10 * i for i, w in enumerate("_ _ twenty thirty forty fifty sixty seventy eighty ninety".split()) if w != "_"}
SCALES = {"hundred": 100, "thousand": 1000, "million": 10 ** 6}


def digits(hyp):
    """The transcriber writes numbers as words ("four hundred and forty-five thousand"); the text has
    "$445,000". Turn each run of number words back into figures, so the two can be lined up."""
    parts = []                                  # split "twenty-twenty-six" into its words, sharing the time
    for t, s, e in hyp:
        bits = t.split("-") if "-" in t and any(norm(b) in SMALL or norm(b) in TENS for b in t.split("-")) else [t]
        parts += [[b, s + (e - s) * k / len(bits), s + (e - s) * (k + 1) / len(bits)] for k, b in enumerate(bits)]
    isnum = lambda w: norm(w) in SMALL or norm(w) in TENS or norm(w) in SCALES
    scale_next = lambda k: k + 1 < len(parts) and norm(parts[k + 1][0]) in SCALES
    out, k = [], 0
    while k < len(parts):
        starts = (isnum(parts[k][0]) and norm(parts[k][0]) not in SCALES) or (norm(parts[k][0]) == "a" and scale_next(k))
        if not starts:
            out.append(parts[k]); k += 1; continue
        run, j = [], k
        while j < len(parts):
            w = "one" if j == k and norm(parts[j][0]) == "a" else norm(parts[j][0])     # "a hundred and eighty"
            if isnum(w) or (w in ("and", "point") and j + 1 < len(parts) and isnum(parts[j + 1][0]) and run):
                run.append(w); j += 1
                # A comma after "thousand" or "million" is inside the number ("fourteen thousand,
                # eight hundred and one" is 14,801; the captions once showed "14,000, 801").
                inside = (parts[j - 1][0].endswith(",") and SCALES.get(w, 0) >= 1000
                          and j < len(parts) and isnum(parts[j][0]))
                if re.search(r"[.,?!;:]$", parts[j - 1][0]) and not inside:
                    break
            else:
                break
        words = [w for w in run if w != "and"]
        whole, frac = (words[:words.index("point")], words[words.index("point") + 1:]) if "point" in words else (words, [])
        groups = []                             # two-digit groups: "twenty" "twenty six" -> 20, 26
        for w in whole:
            if w in SMALL and SMALL[w] < 10 and groups and groups[-1][1]:
                groups[-1] = [groups[-1][0] + SMALL[w], False]
            else:
                groups.append([SMALL.get(w, 0) + TENS.get(w, 0), w in TENS])
        if not frac and not any(w in SCALES for w in whole) and len(groups) == 2 and min(g[0] for g in groups) >= 10:
            value, year = groups[0][0] * 100 + groups[1][0], True     # a year: "nineteen ninety-eight"
        else:
            total, cur, year = 0, 0, False
            for w in whole:
                if w in SCALES:
                    if SCALES[w] == 100:
                        cur = (cur or 1) * 100
                    else:
                        total += (cur or 1) * SCALES[w]; cur = 0
                else:
                    cur += SMALL.get(w, 0) + TENS.get(w, 0)
            value = total + cur
        text = (str(value) if year or value < 1000 else f"{value:,}") + ("." + "".join(str(SMALL[w]) for w in frac if w in SMALL) if frac else "")
        if len(whole) == 1 and not frac and value < 10 and not (j < len(parts) and norm(parts[j][0]) in ("percent", "dollars", "dollar")):
            text = parts[k][0]                  # "one", "two" in a sentence stay words
        else:
            tail = re.search(r"[.,?!;:]*$", parts[j - 1][0]).group(0)
            if j < len(parts) and norm(parts[j][0]) == "percent":
                tail = "%" + re.search(r"[.,?!;:]*$", parts[j][0]).group(0); j += 1
            elif j < len(parts) and norm(parts[j][0]) == "plus" and not tail:
                # "24+ years" is read "twenty-four plus years": without this the captions
                # showed "24+ plus years" (cash is king, 22 Oct 2026).
                tail = "+" + re.search(r"[.,?!;:]*$", parts[j][0]).group(0); j += 1
            elif j < len(parts) and norm(parts[j][0]) in ("dollars", "dollar"):
                text, tail = "$" + text, re.search(r"[.,?!;:]*$", parts[j][0]).group(0); j += 1
            text += tail
        out.append([text, parts[k][1], parts[j - 1][2]])
        k = j
    return out


def norm(w):
    return re.sub(r"[^a-z0-9']", "", w.lower().replace("’", "'")).strip("'")


def tokens(text):
    """The recorded text as display words. narrate.py turned dashes into ' - '; show them as dashes."""
    out = []
    for t in text.split():
        if not norm(t) and out:
            out[-1] = out[-1] + (" —" if t == "-" else " " + t)
        elif norm(t):
            out.append(t)
    return out


def align(ref_words, hyp):
    """Words with times: the text's spelling where it matches the recording, the recording's where not."""
    rn = [norm(w) for w in ref_words]
    hn = [norm(h[0]) for h in hyp]
    out, report = [], []
    fix = lambda w: re.sub(r"[A-Za-z'’]+", lambda m: FIXES.get(m.group(0).lower().replace("’", "'"), m.group(0)), w)
    for tag, i1, i2, j1, j2 in difflib.SequenceMatcher(None, rn, hn, autojunk=False).get_opcodes():
        if tag == "equal":
            out += [(ref_words[i1 + k], hyp[j1 + k][1], hyp[j1 + k][2]) for k in range(i2 - i1)]
            continue
        said = " ".join(h[0] for h in hyp[j1:j2])
        text = " ".join(ref_words[i1:i2])
        close = difflib.SequenceMatcher(None, " ".join(rn[i1:i2]), " ".join(hn[j1:j2])).ratio() >= 0.6
        figure = re.search(r"\d", text) and i2 - i1 <= 2 and j2 - j1 <= 3     # "$1.50" said "one fifty"
        if tag == "replace" and (close or figure):
            # a mishearing: show the text's words, spread over the time the recording took
            s, e, n = hyp[j1][1], hyp[j2 - 1][2], i2 - i1
            out += [(ref_words[i1 + k], s + (e - s) * k / n, s + (e - s) * (k + 1) / n) for k in range(n)]
            if norm(text.replace(" ", "")) != norm(said.replace(" ", "")):
                report.append(("heard differently, text shown", text, said, hyp[j1][1]))
        elif tag == "delete":
            report.append(("in the text, not in the recording", text, "", out[-1][2] if out else 0))
        else:
            out += [(fix(h[0]), h[1], h[2]) for h in hyp[j1:j2]]
            report.append(("recording differs, recording shown", text, said, hyp[j1][1]))
    return out, report


def cues(words):
    out, cur = [], []
    for k, w in enumerate(words):
        cur.append(w)
        nxt = words[k + 1] if k + 1 < len(words) else None
        text = " ".join(x[0] for x in cur)
        end_sentence = re.search(r"[.?!:”\"]$", w[0])
        if (nxt is None
                or len(text) + 1 + len(nxt[0]) > CUE_CHARS
                or nxt[1] - w[2] > 0.7
                or (end_sentence and len(text) >= 18)
                or (w[0].endswith(",") and len(text) >= 48)):
            out.append([cur[0][1], cur[-1][2], text])
            cur = []
    for a, b in zip(out, out[1:]):          # hold each cue until the next one, unless there is a long pause
        a[1] = min(max(a[1] + 0.35, a[0] + 0.8), b[0])
    return out


def ts(t, sep=","):
    h, m, s = int(t // 3600), int(t % 3600 // 60), t % 60
    return f"{h:02d}:{m:02d}:{s:06.3f}".replace(".", sep)


def two_lines(text):
    if len(text) <= 40:
        return text
    mid, best = len(text) // 2, None
    for m in re.finditer(" ", text):
        if best is None or abs(m.start() - mid) < abs(best - mid):
            best = m.start()
    return text[:best] + "\n" + text[best + 1:]


def write_srt(cs, path):
    with open(path, "w", encoding="utf-8") as f:
        for n, (s, e, t) in enumerate(cs, 1):
            f.write(f"{n}\n{ts(s)} --> {ts(e)}\n{two_lines(t)}\n\n")


def write_ass(cs, path):
    esc = lambda t: t.replace("\\", "\\\\").replace("{", "(").replace("}", ")")
    with open(path, "w", encoding="utf-8") as f:
        f.write("[Script Info]\nScriptType: v4.00+\nPlayResX: 1920\nPlayResY: 1080\nWrapStyle: 0\nScaledBorderAndShadow: yes\n\n"
                "[V4+ Styles]\nFormat: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, "
                "Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, "
                "Alignment, MarginL, MarginR, MarginV, Encoding\n"
                # cream Archivo on the merlot panel, centred in the column right of the art
                "Style: Cap,Archivo,66,&H00F3F7FB,&H00F3F7FB,&H00453245,&H00000000,-1,0,0,0,100,100,0,0,1,0,0,5,1150,70,0,1\n\n"
                "[Events]\nFormat: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n")
        for s, e, t in cs:
            f.write(f"Dialogue: 0,{ts(s, '.')[1:-1]},{ts(e, '.')[1:-1]},Cap,,0,0,0,,{esc(t)}\n")


def chapters(p, words):
    """YouTube chapters from the article's headings, found in the spoken words."""
    heads = [html.unescape(re.sub(r"<[^>]+>", "", h)).strip() for h in re.findall(r"<h2>(.*?)</h2>", p["body"], re.S)]
    wn = [norm(w[0]) for w in words]
    out, at = [(0.0, "Introduction")], 0
    for h in heads:
        hn = [norm(t) for t in h.split() if norm(t)]
        if not any(wn[k:k + len(hn)] == hn for k in range(at, len(wn))):
            hn = hn[:3]                         # re-worded since it was recorded: find it by its opening words
        for k in range(at, len(wn) - len(hn) + 1):
            if wn[k:k + len(hn)] == hn:
                if words[k][1] - out[-1][0] >= 10:
                    out.append((words[k][1], h.rstrip(".")))
                at = k + len(hn)
                break
    return out if len(out) >= 3 else []


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("slug")
    ap.add_argument("--episode", type=int, help="its RSS.com episode number (episodes go out in article-date order)")
    ap.add_argument("--note", default="", help="a line above the link, e.g. the recording date")
    ap.add_argument("--numbered-label", default="", help="the label narrate.py was given (a read-aloud episode whose headings are \"1. …\")")
    ap.add_argument("--frame-only", action="store_true")
    a = ap.parse_args()

    p = narrate.post(a.slug)
    mp3 = os.path.join(ROOT, "assets", "audio", f"{a.slug}.mp3")
    art = os.path.join(ROOT, "tools", "podcast", "art", f"{a.slug}.jpg")
    for f in (mp3, art):
        if not os.path.exists(f):
            sys.exit(f"missing {os.path.relpath(f, ROOT)}" + (" (node tools/covers/blog.js <slug> --podcast)" if f == art else ""))
    out = os.path.join(ROOT, "exports", "youtube", a.slug)
    os.makedirs(out, exist_ok=True)

    frame = os.path.join(out, "frame.png")
    with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False) as f:
        json.dump({"art": art, "eyebrow": " · ".join(x for x in (f"Episode {a.episode}" if a.episode else "", p.get("cat", "").title()) if x),
                   "note": a.note,
                   "footer": "erikakpage.com/" + (p.get("short") or "blog/" + a.slug).lstrip("/")}, f)
    subprocess.run(["node", os.path.join(HERE, "frame.js"), f.name, frame], check=True, env={**os.environ, "NODE_PATH": NODE_PATH})
    os.unlink(f.name)
    if a.frame_only:
        return

    script = os.path.join(ROOT, "tools", "podcast", f"{a.slug}.txt")
    episode = bool(p.get("audio_note")) and os.path.exists(script) and "podcast episode" in p["audio_note"]
    text = narrate.read_script(script)[0] if episode else narrate.script(p, a.numbered_label)
    ref = [w[0] for w in digits([[w, 0, 0] for w in tokens(text)])]      # a script's "twenty twenty-seven" shows as 2027
    words, report = align(ref, digits(timings(a.slug, mp3)))
    for kind, t, said, at in report:
        print(f"  {ts(at)[3:8]}  {kind}\n          text: {t[:200]}\n          said: {said[:200]}")

    cs = cues(words)
    write_srt(cs, os.path.join(out, f"{a.slug}.en.srt"))
    ch = [] if episode else chapters(p, words)
    with open(os.path.join(out, "chapters.txt"), "w") as f:
        f.write("".join(f"{int(t // 60)}:{int(t % 60):02d} {h}\n" for t, h in ch))
    print(f"  {len(cs)} captions, {len(ch)} chapters")

    dur = float(subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", mp3],
                               capture_output=True, text=True, check=True).stdout)
    with tempfile.TemporaryDirectory() as tmp:
        ass = os.path.join(tmp, "c.ass")
        write_ass(cs, ass)
        video = os.path.join(out, f"{a.slug}.mp4")
        subprocess.run(["ffmpeg", "-v", "error", "-y", "-loop", "1", "-framerate", "24", "-i", frame, "-i", mp3,
                        "-filter_complex",
                        f"[0:v]ass={ass}[c];color=c=0xC9A15E:s=1920x8:r=24[bar];"
                        f"[c][bar]overlay=x='-w+w*t/{dur:.3f}':y=H-8[v]",
                        "-map", "[v]", "-map", "1:a", "-t", f"{dur:.3f}",
                        "-c:v", "libx264", "-preset", "medium", "-tune", "stillimage", "-crf", "22", "-pix_fmt", "yuv420p",
                        "-c:a", "aac", "-b:a", "160k", "-ar", "44100", "-movflags", "+faststart", video], check=True)
    print(f"wrote {os.path.relpath(video, ROOT)} ({os.path.getsize(video) >> 20} MB, {dur:.0f} s)")
    write_upload(p, a, ch, episode, os.path.join(out, "upload.md"))


def write_upload(p, a, ch, episode, path):
    """The paste-ready YouTube fields. The AI line and the close are the podcast's own (CLAUDE.md, step 8)."""
    site = "https://erikakpage.com"
    title = html.unescape(p["title"])
    desc = [html.unescape(p.get("seo_desc") or p.get("excerpt", "")), "",
            f"Read the article: {site}/blog/{a.slug}"]
    if p.get("product"):
        desc.append(f"Free guide: {site}/digital-products/{p['product']}")
    desc += [f"What's your home worth? {site}/home-value", "",
             # Apple first, Spotify second, never Amazon Music (Erika, 19 Oct 2026)
             "Listen on Apple Podcasts: https://podcasts.apple.com/podcast/id6822448035",
             "Listen on Spotify: https://open.spotify.com/show/1yBRzP6unSx9jrUsofuPs4", ""]
    if ch:
        desc += [f"{int(t // 60)}:{int(t % 60):02d} {h}" for t, h in ch] + [""]
    desc.append("Voiced by an AI trained on Erika's own voice. Researched from the sources linked in the article."
                if episode else "This episode is narrated with an AI voice trained on Erika's own. The words are hers.")
    if a.note:
        desc.append(a.note)
    desc += ["", "This explains how things generally work. It isn't legal, tax or financial advice.",
             "Erika K. Page, Axen Realty. Equal Housing Opportunity.", "", "#ErikaExplains #MetroAtlanta #GeorgiaRealEstate"]
    yt_title = (title.rstrip(".") + " | Erika Explains" + (f" Ep. {a.episode}" if a.episode else ""))[:100]
    tags = list(dict.fromkeys([*p.get("tags", []), "Erika Explains", "Erika Page", "Axen Realty", "Georgia real estate", "Metro Atlanta"]))
    with open(path, "w", encoding="utf-8") as f:
        f.write(f"# YouTube upload: {title}\n\n"
                f"Files: `{a.slug}.mp4` (video), `{a.slug}.en.srt` (captions), thumbnail "
                f"`tools/youtube/thumbs/{a.slug}.jpg`.\n\n"
                f"## Title\n\n```\n{yt_title}\n```\n\n"
                f"## Description\n\n```\n" + "\n".join(desc) + "\n```\n\n"
                f"## Tags\n\n```\n{', '.join(tags)}\n```\n\n"
                "## Settings\n\n"
                "- Playlist: **Erika Explains**, set as a podcast (YouTube Studio → Content → Playlists → ⋮ → Set as podcast)\n"
                "- Audience: **No, it's not made for kids**\n"
                "- Altered or synthetic content: **Yes**. The voice is an AI clone of a real person, which YouTube asks creators to disclose\n"
                "- Category: **Education**. Video language and caption language: **English**\n"
                f"- Subtitles: upload `{a.slug}.en.srt` as English (YouTube's automatic captions write \"Erica\")\n"
                "- Thumbnail: upload the file above. Comments on; publish as Public\n")


if __name__ == "__main__":
    main()
