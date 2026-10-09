# Erika Page website — notes for the next session

Read this before working here. It is the operating knowledge from building the blog,
the guides and the products on erikakpage.com: how to publish, what must not break,
and what took time to find out. `README.md` covers how the site is built; this covers
how to work on it.

**Erika's own knowledge base is the sibling repo `ceo_erika`** (her "second brain").
Its rule is the rule here too: **never invent a fact about Erika.** Every quote, claim
and figure in anything published comes from a file in that repo. Its note
`memory/business/erikakpage-blog.md` records what has been published and which of her
recordings are already used.

---

## The loop — after every change, before the work counts as done

The owner's standing instruction: *the repo is always up to date on everything, so
every new session starts knowing what the last one knew.* After any publish, fix or
change:

1. **Log it** — add an entry at the top of `SESSION-LOG.md`: what was asked, what was
   done, what passed, what failed and how it was fixed, what is still open.
2. **Fold the lesson in** — a new trap or check goes into the right section of this file
   (publishing steps, checks table, environment). A log entry alone is not enough; this
   file is the current truth, the log is the history.
3. **Update** *What's live* and *Open items* below.
4. **`ceo_erika`** — when content was published, update
   `memory/business/erikakpage-blog.md` there (article table, material used, open
   questions for Erika), add a CHANGELOG entry, run `python3 scripts/validate.py &&
   python3 scripts/build_persona.py`, commit and push.
5. **Merge to the default branch and push both** (next section).

The harness enforces part of this (`.claude/settings.json`): a **SessionStart** hook
(`tools/hooks/session-start.sh`) prints the branch state, the newest log entry and the
open items; a **Stop** hook (`tools/hooks/stop-check.sh`) blocks ending a turn — once —
while there are uncommitted or unpushed changes, work not merged into the default
branch, or site changes newer than the last log entry.

## Branch — one branch, read first

**The default branch, `claude/client-website-ux-ra0p3o`, is the single source of
truth** (the owner's rule, 2 Oct 2026: "always use the default branch… always merge the
new branches so the default is updated"). It holds the live PHP site; the old static
mockup was merged in and retired that day.

- Whatever branch a session is given, start with `git fetch origin
  claude/client-website-ux-ra0p3o && git merge origin/claude/client-website-ux-ra0p3o`.
- End every piece of work by bringing the default up to it — `git push origin
  HEAD:claude/client-website-ux-ra0p3o` (a fast-forward once the default is merged in)
  — and pushing the session branch too.
- Plain merges only. Never force-push, never rewrite history.

## Secrets — never commit them

The ElevenLabs key, the Pexels and Pixabay keys and the cPanel user and API token are
**supplied by the owner each session** and passed as environment variables
(`ELEVENLABS_API_KEY`, `CPANEL_USER`, `CPANEL_TOKEN`). Nothing secret is in this repo;
keep it that way, and grep for any key you were given before every push.

**Ask for the keys at the start of any session that will narrate, fetch stock photos or
deploy.** A key given in an earlier part of a long session can't be dug back out of the
transcript after the context is compacted — the permission checker blocks reading
credentials from transcripts (2 Oct 2026), and fetching stock sites without the API key
is blocked the same way. Without keys you can still write, illustrate from the photo
library, render covers, verify locally, commit and push. An older
Gemini key was once exposed in a public `text.php` (since deleted) — rotating it in
Google AI Studio is still outstanding.

---

## What's live

| Article (`/blog/<slug>`) | Category | Short link | Guide it offers |
|---|---|---|---|
| `closing-costs-in-georgia-explained` | Buying | `/closing-costs-101` | Closing Costs 101 |
| `owning-vs-investing-in-real-estate` | Investing | `/invest` | The Hidden-Value Checklist |
| `know-your-house-before-you-list` | Selling | `/before-you-list` | none (no seller guide yet) |
| `cash-is-king-if-you-really-mean-cash` | Buying | `/cash-is-king` | none |
| `georgia-homestead-exemption-explained` | Buying | `/homestead` | none — **researched**, with a Sources section and a podcast-episode audio |
| `relocating-to-metro-atlanta` | Lifestyle | `/moving-to-atlanta` | none — researched around her relocation video; episode recorded 9 Oct |
| `leasing-a-restaurant-space` | Investing | `/restaurant-space` | none — researched around her nine restaurant-space reels; episode recorded 9 Oct |
| `the-open-house-is-a-sales-event` | Selling | `/open-house` | none — researched around her reel with Evelyn (9 Oct); Evelyn's words credited to Evelyn |

Guides (`/digital-products/<slug>`, PDFs in `assets/guides/`, sources in
`tools/guides/`): `closing-costs-101`, `hidden-value-checklist`, `landlord-rent-guide`.
Their short links: `/closing-costs-guide`, `/hidden-value`, `/landlord-guide`.

Every article is narrated in Erika's cloned voice and goes out as an episode of the
**Erika Explains** podcast on RSS.com — the owner uploads; see *Podcast* below.

---

## Two kinds of article — decide first

- **From her recordings** (the first four): the article is her own words, edited; the
  audio is the article read aloud. Follow *Publishing an article* below.
- **Researched** (from 5 Oct 2026 — the owner's instruction: "do research, write with
  real info with references from reputable sources… make the audio sound like a real
  podcast, someone talking, not a page being narrated"). Follow *Research articles and
  podcast episodes* below as well as the publishing run. **This is the default for new
  articles unless the owner asks for one from a recording.**

## Publishing an article — the whole run

1. **Pick the material** from `ceo_erika` (transcripts in `intake/transcripts/video/`,
   reels in `intake/reels/erika-ig/_analysis/*.stt.json`, her written captions in
   `intake/reels/erika-ig/index.json`). Check the blog note there first so you don't
   reuse recordings. Prefer a topic where she speaks at length; a 180-word clip is not
   an article, and one a guide already used is a repeat.
2. **Write it in her voice and to her rules** (they are in `ceo_erika/memory/voice/`
   and `memory/patterns/accuracy-over-claims.md`):
   - hook first, then "Let me explain"; signpost before the important part; it must be
     able to end "…so that's X, explained";
   - **"24+ years"** only — never 25, 26 or 27 in her written copy (the transcripts
     disagree; the brand rule is 24+); **"Erika" with a k** (transcripts say "Erica");
     **Axen Realty** on the byline; no prices;
   - never: "leverage", "utilize", "robust", "navigate the real estate journey",
     "without further ado", "the ATL market" (it's Metro Atlanta), "master suite" (it's
     owner's suite);
   - **no undated market figures.** Her August-2026 market table is marked expired and
     contradicts the July figure the closing-costs article cites. An illustration of
     her own (e.g. "$400,000 × 5% = $20,000") is fine;
   - where her on-camera advice could backfire in writing, add a general caution and
     flag it to the owner — e.g. the selling article's disclosure note, and leaving out
     her suggestion that buyers skip their own inspection. Never assert specific law.
3. **Add it to `posts.php`** (newest first in the file isn't required; `blog_posts()`
   sorts by date). Fields: `slug, title, seo_title, seo_desc (~155 chars), excerpt,
   date, updated, author, cat, tags, cover, cover_alt, cover_credit, audio,
   audio_secs, guide, product, short, published, faq, body`.
   - `product` — slug of the matching guide. One field joins the two: the article's
     offer box takes its cover, name and page count from the product, and the
     product page shows a card linking back. Leave `guide` and `product` empty for an
     article with no guide; the page and form handle that.
   - `short` — a sayable path (`/invest`); it 301s to the article.
   - Body pictures use `[[img:path|alt|credit]]`, never a raw `<img>`.
   - Strings in single-quoted PHP: escape apostrophes (`Sam\'s`).
   - Entities (`&rsquo;`, `&mdash;`) are fine in `title`, `seo_title`, `seo_desc`,
     `excerpt` and `cover_alt`: `blog_clean()` decodes them once with `plain_text()`
     before they are escaped. Until 2 Oct 2026 it didn't, and every `/blog` card and
     article intro showed "&rsquo;" as text. Photo `label`s in `photos.php` are not
     decoded — keep those plain.
   - A short source makes a short article: explain her points, don't pad them. The
     cash-is-king article (2 Oct) came from ~320 words of hers and runs ~900.
4. **Pictures — every article gets its own.** **Never reuse a picture another article
   uses**, whether as a body picture or as a cover tile, and don't repeat the article's
   own body picture as its cover tile. The owner called the old habit "soooo lazy"
   (7 Oct 2026): one signing photo was on two articles four times, and two more were
   shared. It happened because this step said to reuse library photos, and there was no
   Pexels key. `check-article.mjs` now fails on any repeat. Find fresh ones with
   `PEXELS_KEY=… python3 -I tools/stock-search.py <scratch-dir> "<query>"`, look at
   the numbered `sheet.jpg`, pick one that fits the topic, crop it to **1600×1000 (1.6)**
   like every editorial picture, ~150–260 KB, credited, registered in `photos.php` library `17`, then
   `php tools/build-images.php`. Look at every candidate: stock is often recognisably
   foreign (Turkish signage on one rejected shot). Never upscale.
5. **Cover** — the house template is **Bold**. Add the article to
   `tools/covers/blog.json` (`template: "bold"`, `eyebrow` = category, `line` = title,
   `sub` = a line of hers, `portrait`, `ref` = a small reference picture, optional
   `refPos`) and run `NODE_PATH=/opt/node22/lib/node_modules node tools/covers/blog.js
   <slug>`. It writes `assets/photos/17/<slug>-cover.jpg`, sizes the headline to fit,
   and refuses to write a cover with an upscaled picture or overflowing text. Then
   **look at it** at full size and at ~290 px wide (the `/blog` card): the checks can't
   see text running into her hair. The cover is also the `og:image` — it's what shows
   when the link is shared.
   - The portrait used on every cover, `assets/photos/01/a7-pink-suit-ai.jpg`, is
     labelled **AI-generated** in `photos.php`. It was the site operator's choice; if
     Erika wants a real photo, it's one field in `blog.json`.
   - The Sam's Club tile is the sign cut from her own video, not a logo file: keep
     third-party marks small and never worded as a partnership.
   - Don't start a headline line with a dash — the auto-fit can wrap "— if" to the
     start of line two. Use a comma, and keep the post `title` the same as the
     cover's `line`. The `sub` must add something, not repeat the headline.
6. **Narration** — `ELEVENLABS_API_KEY=… python3 tools/narrate.py <slug>` (add
   `--numbered-label Reason` if headings are "1. …"; `--dry-run` first to read the
   script). Then `python3 tools/stt-check.py assets/audio/<slug>.mp3 --at <seam>` and
   compare with the text — figures and near-homophones are where it goes wrong ("not
   a valuation" came back "not evaluation"; rewrite the sentence, regenerate). Put the
   printed `audio_secs` on the post.
7. **Verify** — `NODE_PATH=/opt/node22/lib/node_modules node tools/check-article.mjs
   --all` against the local server, then against the live site after deploying (next
   section), **commit**, rebuild `dist/erikakpage-site.zip` from the
   tracked files (`git ls-files` minus `config.php` and `dist/`), push, **deploy**.
8. **Podcast episode** — hand the owner the MP3 plus paste-ready title and
   description: the article and `/home-value` links, the line *"This episode is
   narrated with an AI voice trained on Erika's own. The words are hers."*, and the
   explanatory-only / Axen Realty / Equal Housing close. Episode type Full, not
   explicit.

## Research articles and podcast episodes

The worked example is the homestead article: `tools/research/georgia-homestead-exemption-explained.md`
and `tools/podcast/georgia-homestead-exemption-explained.txt`. Copy their shape.

1. **Pick a topic that is useful and Georgia-specific**, and if possible one she has
   touched on camera (the homestead article closed a promise from her reel
   `Dbs_B2MBThx`). Check `ceo_erika`'s blog note for what's owed and what's used.
2. **Research, primary sources first:**
   - official sources first: state agencies (georgia.gov, dor.georgia.gov), the bill as
     signed (gov.georgia.gov/document/…), county tax or assessor offices;
   - then reputable reporting and research: WABE, AJC, GPB, Tax Foundation, GBPI, GMA,
     ACCG;
   - commercial explainers (Ownwell and the like) only for a point no official source
     states, and say so in the text ("analyses of the law expect…").
   Many official and news sites answer 403 to anything that isn't a browser (BLS,
   Census Reporter, Fulton's assessor, ARC, the regional transit authority, 11Alive,
   sos.ga.gov, atlantaga.gov, dph.georgia.gov, and WABE from 7 Oct). Official PDFs
   often download fine with `curl -A "Mozilla/5.0 …"`: save them to a fresh scratch
   folder and read them with the venv's pypdf (`python -I`). That route gave the Fulton
   and GNR health-department documents, the Gwinnett fire CO leaflet and GDOT's TADA
   guide on 7 Oct. Check each document's date; the Gwinnett leaflet is from 2012, so
   it's starred and used only as an example. WebFetch reads some of them (BLS, Census Reporter). If nothing can read
   a source, leave the fact out — the relocation notes list three facts dropped this
   way. The Census Bureau's own data API now needs a key; Census Reporter shows the
   same ACS figures.
   Read the source text itself, not a search snippet. **Search results disagreed on
   SB 33**: one headline read "Georgia rejects property tax caps". Reading the pages
   showed the broader caps failed and SB 33 passed. Resolve every conflict at the source.
3. **Write `tools/research/<slug>.md`**: a sources table (number, name, type, URL), then
   one row per claim — the claim as used, the source number and **the words in the
   source**. Star (★) anything dated (deadlines, percentages, law). List what you
   deliberately left out and why. Every fact in the article and the script must be a
   row here.
4. **The article** keeps her voice and rules (*Publishing* step 2), links each fact
   inline to its source (`target="_blank" rel="noopener"`), and ends with a
   `<h2>Sources</h2>` numbered list. Add the one-line "explains how it works; isn't tax
   or legal advice; confirm with your county" note before it. **Nothing about Erika she
   didn't say:** quote her recordings exactly, attribute paraphrase ("I said something
   like…"), and invent no feelings, client stories or results.
   **Check who is speaking.** The reel transcripts have no speaker labels. In
   `Dbt77Dkuk44` the lines everyone remembers ("this is a sales event", "know who's
   there for the right reason") are her colleague Evelyn's, saying what she learned from
   Erika. Read the turns ("what I learned from Erica…", "she taught me that") and credit
   each line to whoever said it.
5. **The podcast script** `tools/podcast/<slug>.txt`, written for the ear:
   - she talks to one listener — "Hey, it's Erika. Welcome back to Erika Explains." …
     "So that's X, explained. I'm Erika Page. Thanks for listening, and I'll talk to you
     next time.";
   - contractions, short sentences, questions to the listener, an "…" where she would
     pause; signpost the important part ("Now here's the important part");
   - no headings, no list read as a list ("Three things. First… Second… And third…"),
     no URLs or citations read out — "I've put links to every source in the show
     notes";
   - numbers and years as spoken words ("April first, twenty twenty-seven", "House Bill
     five eighty-one", "sixty-eight percent") — the voice gets them right that way;
   - same facts as the article, nothing new; 6–8 minutes (~1,100–1,200 words; the
     restaurant script is 1,218);
   - audit it against her recordings like the article: small flourishes slip in ("out
     and in", "No kitchen. Nothing.") that she never said;
   - the `# voice:` line keeps the settings: `stability=0.38 similarity=0.8 style=0.22
     speaker_boost=1` gave a natural, less read-aloud delivery on 5 Oct 2026. Keep it
     for consistency between episodes.
6. **Audio**: `ELEVENLABS_API_KEY=… python3 tools/narrate.py <slug> --script
   tools/podcast/<slug>.txt` (`--dry-run` first; a script under ~900 words runs about
   5 minutes, so aim for ~1,100 words for 6–8; 1,072 words ran 404 s), then
   `stt-check.py <mp3> --full` — the whole episode in one call — and read it against the
   script: every seam, every date, bill or percentage, and the ending. (Windowed `--at`
   checks are for a single passage you've regenerated.)
   ElevenLabs' transcriber writes "Erica"; the voice says her name correctly.
7. **Set `audio_note` on the post** ("A podcast episode on this topic, in Erika's AI
   voice. The sources are listed at the end of the article."). Without it the page says
   "Same words, read aloud", which is false for an episode. `check-article.mjs` checks
   it.
8. **Podcast description** for a researched episode: the AI line becomes *"Voiced by an
   AI trained on Erika's own voice. Researched from the sources linked below."* ("The
   words are hers" is only true for recordings). Then list the sources.
9. **Flag it for Erika.** A researched article and script are words in her voice that
   she didn't say. Send the owner a short note for her to read it.

## Checks, and the bug behind each

`tools/check-article.mjs` runs the article checks in one go and prints PASS/FAIL per
line (`--all`, or slugs; `--base` local or the live mirror, `--origin
https://erikakpage.com` for the redirect check). When a new bug is found, add its
check there and a row here.

| Check | Why it exists |
|---|---|
| Rendered vs natural aspect ratio of every visible image (`object-fit:fill` within 3%) | body pictures once shipped stretched 712×1000 |
| Each article's offer box shows **its own** guide's cover, name, page count and form name; `?sent=1` downloads its own PDF | the box was hard-coded to Closing Costs 101 in eight places |
| An article with no guide: no offer box, form named `Blog enquiry: <title>` | it was named "Guide request: free guide" |
| Admin round-trip keeps `short` and `product` | saving the Blog screen used to blank `short` and 404 the short link |
| Chips filter in a real browser | the products page's chips were decorative |
| Narration duration measured from the file; transcription spot-check | typed durations drift; TTS mishears |
| PDFs: per-character font check, geometry, clipping, each page's `data-must` text | fonts silently fell back; grepping the PDF for a font name gave a false pass |
| No HTML entity shows as text on the page, in the title or the share tags | `&rsquo;` was escaped twice on every `/blog` card (fixed 2 Oct 2026) |
| Every outside link in the article answers (2xx/3xx; 403 = WARN, check by hand) | a researched article is only as good as its references; Fulton's assessor site answers 403 to non-browsers, so it's verified by search instead |
| The line under the audio player matches the post (`audio_note`, or the "read aloud" default) | it said "same words, read aloud" for any audio; untrue for a podcast episode |
| Admin round-trip keeps `audio_note` too | proven 5 Oct 2026 on a throwaway copy with a test login (see SESSION-LOG) |
| **No picture on two articles**; no cover tile repeats its own article's body picture (whole blog, every run) | the same signing photo was on two articles four times, and two more were shared — the owner called it lazy (7 Oct 2026) |
| Short link answers **301** to the article (with `#guide` only when there is a guide) | a 404 after an admin save; a `#guide` anchor on an article with no guide |
| **Live**: byte-compare every upload; confirm pages by completeness (`</html>`, >150 KB), not status | see the host's holding page below |

## Deploying, and the environment

- `CPANEL_USER=… CPANEL_TOKEN=… tools/deploy.sh <files…>` uploads, creates
  directories, and confirms each file's size from a fresh listing.
- **The host serves an anti-bot holding page ("One moment, please…") at random, with
  HTTP 200.** A 200 proves nothing — not for uploads, pages or redirects. Retry until
  the body is the real one.
  It can also answer a single picture request that way, so a picture shows as "not
  loaded" though the file is fine. `check-article.mjs` reloads once before failing one;
  if a picture still fails, fetch the file and byte-compare it before believing it.
- The egress proxy sometimes truncates large transfers; retry on a short body, not
  just on an error.
- Headless Chromium doesn't trust the egress proxy's CA, so it can't open the live
  site. Run `python3 tools/live-mirror.py 8731` and point the browser at
  `http://127.0.0.1:8731` — the mirror verifies TLS itself. **Never** disable
  certificate checks. The mirror follows redirects itself, so check short links
  against the real origin (`check-article.mjs --origin https://erikakpage.com`).
- Playwright: `NODE_PATH=/opt/node22/lib/node_modules`, or import
  `/opt/node22/lib/node_modules/playwright/index.js`; Chromium is at
  `/opt/pw-browsers/chromium`. Lazy images only load when scrolled into view.
- Local server: `php -S 127.0.0.1:8000 router.php` (needed for clean URLs). It can
  serve a just-changed PHP file stale for a second or two; when a check fails right
  after an edit or `git stash pop`, run it again before believing it.
- `mp3_duration()` takes a path **relative to the site root**; an absolute path
  returns 0.
- Killing helpers: `pkill -f <pattern>` can match your own shell and kill it. Find the
  PID with `ps -eo pid,args | grep …` and `kill` that.
- Reading PDFs (bills, DOR bulletins): no poppler here, and the system Python's
  `cryptography` package crashes when `pypdf` is imported. Make a venv in the scratchpad
  (`python3 -m venv pdfenv && pdfenv/bin/pip install pypdf`) and extract text there.
  "As passed" bill text mixes struck and new wording without marking it, so quote it
  carefully and cross-check a summary.
- PDF rendering only sees fonts installed through fontconfig, and the brand's
  variable fonts must be cut to static weights first — `tools/build-guide.mjs` does
  both; see the README.

## Content model gotcha

`posts.php` and `products.php` are only the **defaults**. Once anyone saves the Blog
posts or Digital products screen in the admin, the database copy of each item wins and
later edits to the PHP file for the same slug **no longer show**. Before editing a
shipped article in `posts.php`, check whether the admin has saved one (the
`blog.posts` setting); if it has, edit there instead.

## Open items

- **For Erika to review:** the open-house article (9 Oct). Her caption and her
  Smyrna lines are hers; the two headline lines are **Evelyn's**, credited to her —
  check Evelyn is happy to be quoted by name. The safety, fair-housing and NAR facts are
  researched.
- **The voice outage (6–7 Oct) is over** — it worked again on 9 Oct and all eight
  articles have audio. If `narrate.py` stops on `voice_not_fine_tuned` again: publish
  without audio (set `audio_note`, leave `audio` empty), tell the owner (only they can
  fix it in ElevenLabs), and **never substitute another voice or make a new clone of
  Erika** without the owner's and her say-so.
- **For Erika to review:** the restaurant-lease article (7 Oct). Her lines and her
  client's story are quoted from her reels; the permits section is researched. Her
  "$4,000 cheaper" is given as "thousands a month cheaper" (no-prices rule).
- **For Erika to review:** the relocation article (6 Oct). Her four points are quoted
  from her video; the rest is researched and in her voice.

- **Rotate the ElevenLabs key and the cPanel API token** (pasted into chat on 2 Oct
  2026, still active on 9 Oct), **and the Pexels and Pixabay keys** (pasted 7 Oct; Pexels
  still active on 9 Oct). Ask
  the owner for the new ones next session.
- **For Erika to review:** the homestead article and its podcast script (5 Oct). They
  are researched, so they're in her voice but not her words. Also re-check its starred
  facts (★ in the research notes) before 1 April 2027, the filing date it points people
  to.
- **For Erika to review:** the cash-is-king caution ("every contingency you remove is a
  protection you no longer have… never just to win the house"), which is added, not hers.
- **16 mockup buttons** still call `alert(…)` in `index.php`: Escaluxe Living shop
  (×6), phone buttons (×2 — these just need `tel:678-404-1562`), Google booking (×2),
  Axen/Lofty home search (×3), the PM website, YouTube, the media kit.
- **For Erika to review:** page 5 of Closing Costs 101 (Georgia tax rates); the
  landlord guide (its Section 8 source was marked a draft); the selling article's
  disclosure note and the omitted buyer-inspection line; the open "24+ vs 26 years"
  question.
- Rotate the old Gemini key.
- Unreferenced files left on the live host (harmless; delete only with the owner's
  yes): `assets/photos/17/a7-erika-sams-club.jpg`, `a7-apartment-building-balconies.jpg`
  and their `rimg` variants.
- The Seller Strategy Toolkit and the other drafts in `products.php` stay invisible
  until written; each carries a note on what it needs.
