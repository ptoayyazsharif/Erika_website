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

## Branch — read first

All live work is on **`claude/mobile-layout-images-fix-go8oqx`**. The repository's
*default* branch is `claude/client-website-ux-ra0p3o`, the original static mockup — a
session that opens the default branch sees none of this. Work on, and push to, the
live-work branch unless the owner says otherwise.

## Secrets — never commit them

The ElevenLabs key, the Pexels and Pixabay keys and the cPanel user and API token are
**supplied by the owner each session** and passed as environment variables
(`ELEVENLABS_API_KEY`, `CPANEL_USER`, `CPANEL_TOKEN`). Nothing secret is in this repo;
keep it that way, and grep for any key you were given before every push. An older
Gemini key was once exposed in a public `text.php` (since deleted) — rotating it in
Google AI Studio is still outstanding.

---

## What's live

| Article (`/blog/<slug>`) | Category | Short link | Guide it offers |
|---|---|---|---|
| `closing-costs-in-georgia-explained` | Buying | `/closing-costs-101` | Closing Costs 101 |
| `owning-vs-investing-in-real-estate` | Investing | `/invest` | The Hidden-Value Checklist |
| `know-your-house-before-you-list` | Selling | `/before-you-list` | none (no seller guide yet) |

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
   - Strings in single-quoted PHP: escape apostrophes (`Sam\'s`). Fields that are
     escaped on output (`cover_alt`, photo `label`s) must be plain text — an `&rsquo;`
     there shows literally.
4. **Pictures** — Pexels/Pixabay, cropped to **1600×1000 (1.6)** like every editorial
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
6. **Narration** — `ELEVENLABS_API_KEY=… python3 tools/narrate.py <slug>` (add
   `--numbered-label Reason` if headings are "1. …"; `--dry-run` first to read the
   script). Then `python3 tools/stt-check.py assets/audio/<slug>.mp3 --at <seam>` and
   compare with the text — figures and near-homophones are where it goes wrong ("not
   a valuation" came back "not evaluation"; rewrite the sentence, regenerate). Put the
   printed `audio_secs` on the post.
7. **Verify** (next section), **commit**, rebuild `dist/erikakpage-site.zip` from the
   tracked files (`git ls-files` minus `config.php` and `dist/`), push, **deploy**.
8. **Podcast episode** — hand the owner the MP3 plus paste-ready title and
   description: the article and `/home-value` links, the line *"This episode is
   narrated with an AI voice trained on Erika's own. The words are hers."*, and the
   explanatory-only / Axen Realty / Equal Housing close. Episode type Full, not
   explicit.

## Checks, and the bug behind each

| Check | Why it exists |
|---|---|
| Rendered vs natural aspect ratio of every visible image (`object-fit:fill` within 3%) | body pictures once shipped stretched 712×1000 |
| Each article's offer box shows **its own** guide's cover, name, page count and form name; `?sent=1` downloads its own PDF | the box was hard-coded to Closing Costs 101 in eight places |
| An article with no guide: no offer box, form named `Blog enquiry: <title>` | it was named "Guide request: free guide" |
| Admin round-trip keeps `short` and `product` | saving the Blog screen used to blank `short` and 404 the short link |
| Chips filter in a real browser | the products page's chips were decorative |
| Narration duration measured from the file; transcription spot-check | typed durations drift; TTS mishears |
| PDFs: per-character font check, geometry, clipping, each page's `data-must` text | fonts silently fell back; grepping the PDF for a font name gave a false pass |
| **Live**: byte-compare every upload; confirm pages by completeness (`</html>`, >150 KB), not status | see the host's holding page below |

## Deploying, and the environment

- `CPANEL_USER=… CPANEL_TOKEN=… tools/deploy.sh <files…>` uploads, creates
  directories, and confirms each file's size from a fresh listing.
- **The host serves an anti-bot holding page ("One moment, please…") at random, with
  HTTP 200.** A 200 proves nothing — not for uploads, pages or redirects. Retry until
  the body is the real one.
- The egress proxy sometimes truncates large transfers; retry on a short body, not
  just on an error.
- Headless Chromium doesn't trust the egress proxy's CA, so it can't open the live
  site. Run `python3 tools/live-mirror.py 8731` and point the browser at
  `http://127.0.0.1:8731` — the mirror verifies TLS itself. **Never** disable
  certificate checks.
- Playwright: `NODE_PATH=/opt/node22/lib/node_modules`, or import
  `/opt/node22/lib/node_modules/playwright/index.js`; Chromium is at
  `/opt/pw-browsers/chromium`. Lazy images only load when scrolled into view.
- Local server: `php -S 127.0.0.1:8000 router.php` (needed for clean URLs).
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
