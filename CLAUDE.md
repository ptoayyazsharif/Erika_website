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

Guides (`/digital-products/<slug>`, PDFs in `assets/guides/`, sources in
`tools/guides/`): `closing-costs-101`, `hidden-value-checklist`, `landlord-rent-guide`.
Their short links: `/closing-costs-guide`, `/hidden-value`, `/landlord-guide`.

Every article is narrated in Erika's cloned voice and goes out as an episode of the
**Erika Explains** podcast on RSS.com — the owner uploads; see *Podcast* below.

---

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
4. **Pictures** — check the library first: `photos.php` library `17` has credited
   photos no article uses any more (the closing-table photo was free again after the
   Bold covers, and now illustrates the cash article). New ones: Pexels/Pixabay, cropped to **1600×1000 (1.6)** like every editorial
   picture, ~150–260 KB, credited, registered in `photos.php` library `17`, then
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

- **Rotate the ElevenLabs key and the cPanel API token**: both were pasted into chat on
  2 Oct 2026. Ask the owner for the new ones next session.
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
