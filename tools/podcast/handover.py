"""Everything the owner pastes into RSS.com for one episode, in one block.

    python3 tools/podcast/handover.py <slug> --episode <n> [--note "Recorded …"] [--place "Atlanta, Georgia"]

The owner's standing instruction (20 Oct 2026): every hand-over gives *every* field, every
time, in the reply itself. A keyword list that sits at the end of a long message, or only in
a file, counts as missing. This prints the whole block (and saves it to
exports/podcast/<slug>-ep<n>.md) so nothing depends on remembering:

  - the files: the levelled MP3 (checked with level-audio.py) and the 1400x1400 square art;
  - Title;
  - Description: the article's summary, the article and /home-value links, the AI line
    (read-aloud: "The words are hers"; researched: "Researched from the sources linked
    below"), any recording note, the sources with their links, and the explanatory-only /
    Axen Realty / Equal Housing close;
  - Keywords (6–8): the post's tags, topped up with the show's own;
  - the RSS.com settings: Full, Season 1, the episode number, Explicit No, AI content Yes,
    the place it's about, and "Where it's made" left to the owner;
  - dated lines to re-check against today (CLAUDE.md, "Is it still true today?").

Copy the printed block into the reply. Don't summarise it.
"""
import argparse, html, importlib.util, json, os, re, subprocess, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
spec = importlib.util.spec_from_file_location("narrate", os.path.join(ROOT, "tools", "narrate.py"))
narrate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(narrate)

SITE = "https://erikakpage.com"
SHOW_KEYWORDS = ["Metro Atlanta", "Georgia real estate", "Erika Explains", "Erika Page"]


def text(s):
    return html.unescape(re.sub(r"<[^>]+>", "", s or "")).strip()


def sources(body):
    """The article's numbered Sources list, as (name, url) pairs; her own recordings have no url."""
    i = body.find("<h2>Sources</h2>")
    if i < 0:
        return []
    out = []
    for li in re.findall(r"<li>(.*?)</li>", body[i:], re.S):
        urls = re.findall(r'href="([^"]+)"', li)
        out.append((re.sub(r"\s+", " ", text(li)).rstrip("."), urls[0] if urls else ""))
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("slug")
    ap.add_argument("--episode", type=int, required=True)
    ap.add_argument("--note", default="", help='e.g. "Recorded September 29, 2026; the market figures are from July 2026."')
    ap.add_argument("--place", default="Atlanta, Georgia", help="RSS.com: What place this episode is about")
    ap.add_argument("--keywords", default="", help="comma-separated, to replace the ones built from the post's tags")
    a = ap.parse_args()

    p = narrate.post(a.slug)
    mp3 = os.path.join(ROOT, "assets", "audio", f"{a.slug}.mp3")
    art = os.path.join(ROOT, "tools", "podcast", "art", f"{a.slug}.jpg")
    problems = []
    if not os.path.exists(mp3):
        problems.append(f"no MP3 at {os.path.relpath(mp3, ROOT)}")
        level = "MISSING"
    else:
        r = subprocess.run([sys.executable, os.path.join(ROOT, "tools", "level-audio.py"), "--check", mp3], capture_output=True, text=True)
        level = (r.stdout + r.stderr).strip().splitlines()[-1] if (r.stdout + r.stderr).strip() else "?"
        if r.returncode != 0 or not level.startswith("ok"):
            problems.append("the MP3 is not levelled: " + level)
    if not os.path.exists(art):
        problems.append(f"no square art: node tools/covers/blog.js {a.slug} --podcast")

    researched = "podcast episode" in (p.get("audio_note") or "")
    title = text(p["title"])
    summary = text(p.get("seo_desc") or p.get("excerpt") or "")
    desc = [summary, "", f"Read the article: {SITE}/blog/{a.slug}", f"Curious what your home is worth? {SITE}/home-value", ""]
    desc.append("Voiced by an AI trained on Erika's own voice. Researched from the sources linked below."
                if researched else "This episode is narrated with an AI voice trained on Erika's own. The words are hers.")
    if a.note:
        desc.append(a.note)
    src = sources(p["body"])
    if researched and src:
        desc += ["", "Sources:"] + [f"- {n}" + (f": {u}" if u else "") for n, u in src]
    desc += ["", "This episode explains how things generally work and isn't legal, tax or financial advice.",
             "Erika K. Page, Axen Realty. Equal Housing Opportunity."]

    if a.keywords:
        kw = [k.strip() for k in a.keywords.split(",") if k.strip()]
    else:
        kw = []
        for k in list(p.get("tags") or []) + SHOW_KEYWORDS:
            if k.lower() not in [x.lower() for x in kw]:
                kw.append(k)
        kw = kw[:8]
    if not 6 <= len(kw) <= 8:
        problems.append(f"{len(kw)} keywords: RSS.com hand-overs carry 6 to 8")

    secs = int(p.get("audio_secs") or 0)
    dated = []
    script = os.path.join(ROOT, "tools", "podcast", f"{a.slug}.txt")
    said = ("\n".join(l for l in open(script, encoding="utf-8").read().splitlines() if not l.lstrip().startswith("#"))
            if researched and os.path.exists(script) else text(p["body"]))
    for s in re.split(r"(?<=[.!?])\s+", said):
        if re.search(r"right now|currently|this year|most recent|latest|today|twenty twenty|\b20\d\d\b", s, re.I) and not s.lstrip().startswith("#"):
            dated.append(s.strip()[:220])

    out = [f"## Podcast Episode {a.episode}: {title}", "",
           f"Files: `{os.path.relpath(mp3, ROOT)}` ({secs // 60}:{secs % 60:02d}; {level}) and `{os.path.relpath(art, ROOT)}` (1400×1400).", "",
           "**Title**", "", "```", title, "```", "",
           "**Description**", "", "```", *desc, "```", "",
           f"**Keywords ({len(kw)})**", "", "```", ", ".join(kw), "```", "",
           "**RSS.com settings**", "",
           "| Setting | Value |", "|---|---|",
           "| Episode type | Full |",
           f"| Season / episode | Season 1, Episode {a.episode} |",
           "| Explicit | No |",
           "| AI content | Yes |",
           f"| What place this episode is about | {a.place} |",
           "| Where this episode is made | Your choice; her notes only say Metro Atlanta |", ""]
    if dated:
        out += ["**Dated lines to re-check against today** (none of these may say \"right now\" about something that has moved on):", ""]
        out += [f"- {d}" for d in dated] + [""]
    if problems:
        out += ["**NOT READY:**", ""] + [f"- {x}" for x in problems] + [""]
    block = "\n".join(out)
    os.makedirs(os.path.join(ROOT, "exports", "podcast"), exist_ok=True)
    with open(os.path.join(ROOT, "exports", "podcast", f"{a.slug}-ep{a.episode}.md"), "w", encoding="utf-8") as f:
        f.write(block + "\n")
    print(block)
    sys.exit(1 if problems else 0)


if __name__ == "__main__":
    main()
