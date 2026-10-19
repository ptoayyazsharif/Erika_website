# Session log

Newest first. One entry per session or piece of work: what was asked, what was done,
what passed, what failed (and how it was fixed), and what is still open. The lessons
in here are also folded into `CLAUDE.md`, which is the current truth; this file is the
history behind it. See "The loop" in `CLAUDE.md`.

---

## 2026-10-19 (night) — Erika's meeting with her VA: packs for the top articles only

**Asked.** The VA shared Erika's 19 Oct meeting notes and asked for every repo and her second brain
to be brought up to date.

**Done**
- *Social post pack and YouTube video* rewritten: packs for the **top 6–7 articles** by original
  reel traction only, a CTA in every caption, Erika approves first, about one a week, LinkedIn not
  daily; YouTube for every episode; podcast links Apple first, Spotify second, never Amazon Music.
- Open items updated to match.
- In `ceo_erika`: the meeting went into `memory/` (14 new notes), and `STATUS.md` now holds every
  project's progress, the links and the task list. The 15 blog-record commits made by this repo's
  loop on `claude/erika-ai-memory-system-73dbz0` had never reached `ceo_erika`'s `main`; merged.

**Open**
- Re-pull current reel views, then build packs 2–7 in ranking order and send them to Erika.

---

## 2026-10-19 (evening) — One `main` branch with everything in it

**Asked.** "Merge, make a main branch, and all things in one place instead of all the
clutter." The owner chose: the other projects go into `main` as folders, and the old branches
are kept.

**Done**
- **`main` created** from the website's default (`claude/client-website-ux-ra0p3o`, c874d58b923e)
  and pushed. Making it GitHub's default failed from here: "Repository settings writes are
  not permitted through this proxy" (403). The owner switches it in GitHub.
- **Merged into it:**
  - `claude/escalate-app-bugs-l73sgw` (d612bda253c0): the Escalate app, the Stella demo and
    the n8n flow, in `escalate/`, `stella-demo/` and `manifest-n8n/`. It already contained
    `claude/erika-manifestation-demo-jgjpch` (f6356a9b1db1).
  - `claude/ask-erika` (7b65ccfb1550, its own unrelated history) into `ask-erika/`, so it
    doesn't land on the website's `lib/` and `tests/`.
  - `claude/erika-website-photos-6p20hw` and `claude/lite-cms` were already in.
- **Conflicts resolved:**
  - the website's root `CLAUDE.md` and `README.md` won;
  - Escalate's project notes moved to `escalate/CLAUDE.md`, with their links made relative;
  - the two `.gitignore` files were combined.
- **Clutter:**
  - the stale 103 MB `dist/erikakpage-site.zip` (from 13 Oct) left the tree, and `dist/` is
    ignored;
  - the August meeting list, the photo placement guide and the zip's install README moved
    into `docs/`;
  - the meeting's waiting items are now in CLAUDE.md's open items.
- **Hooks** are pinned to `main`. They used to read `origin/HEAD`, which in this clone pointed
  at the session branch.
- **README and CLAUDE.md** describe `main`, the folders, and the zip rule (leave the other
  projects out).
- No secrets in the merged projects (pattern scan of both branches).

**Not changed**
- Every old branch is kept as it was.
- `claude/escalate-app-bugs-l73sgw` must stay: escalate.cloud's Coolify deploy follows it
  (`escalate/COOLIFY.md`).

**Open**
- **Owner:** set `main` as the default branch on GitHub (Settings → General → Default branch).
- When the owner wants Escalate deployed from `main`, Coolify's `git_branch` changes and its
  base directory becomes `escalate/`. That is the owner's step.

---

## 2026-10-19 (later) — Social post packs and YouTube videos: the tools, and the first of each

**Asked.** "A social post pack for each article: 3 captions, a 5-slide carousel in her Bold
cover style, and a Google Business Profile post… The podcast on YouTube. Each episode as a
video, with the square art and captions… let's do it for the first one."

**Done**
- **`tools/social/pack.js`** with `tools/social/<slug>.json`:
  - five 1080×1350 slides in the Bold style;
  - `carousel.pdf` for LinkedIn;
  - `google-business.jpg` (1200×900);
  - `pack.md` with three captions, the Google Business Profile post and alt text.
  - Pack 1 (closing costs): every line taken from the article.
- **`tools/youtube/episode.py`** with `frame.js`:
  - word timings from ElevenLabs, saved in `tools/youtube/timing/`;
  - aligned with the recorded text, numbers turned back into figures;
  - an SRT, burned-in captions on a 1920×1080 frame with the square art, chapters from the
    headings, and `upload.md`.
  - **Thumbnail:** a `--youtube` template in `tools/covers/blog.js` (1280×720).
  - **Episode 1:** 667 s, 239 captions, 11 chapters, 29 MB, with the note "Recorded
    September 29, 2026. The market figures are from July 2026."

**Failed, and what was done**
- **Numbers as words.** The first captions showed "three thousand dollars" and "two-point-one
  percent", because the transcriber writes numbers as words. Added `digits()`; tested on
  amounts, percentages, years, "a hundred and eighty" and "twenty-four".
- **Small captions.** At 50 px they were too small for a phone; now 66 px with shorter cues.
- **Missing chapter.** The market chapter didn't appear: the heading was re-worded after
  recording. Headings now fall back to their first three words.
- **Repeated title on the frame.** The right panel repeated the art's show name and title;
  it now shows only the episode number.
- **Link wrapping.** The end slide's link wrapped mid-word; it is now kept on one line.

**Found**
- Episode 1 (recorded 29 Sep, spot-checked then, before `--full` was the rule): the
  transcriber hears "20,163" at 7:51 (text: 20,863) and "one hour" at 2:42 (text: $1.00), the
  same twice. Flagged to the owner to listen to; the captions show the text's figures.

**Open**
- Ask whether Erika has a Google Business Profile, and where her Instagram bio should point.
- Pack 2 and YouTube episode 2 next.

---

## 2026-10-19 — HUD homes article; podcast episode 6 hand-over

**Asked.** "Post a new blog and give me episode 6."

**Done**
- **Article** `/blog/buying-a-hud-home` (`/hud-homes`, Buying): "Your first home could be a
  foreclosure. Here's how HUD homes work."
  - Built around her unused closing-day reel `DbXJNqdPo6y` ("celebrating a first-time
    homebuyer who scored an incredible foreclosure deal"). The caption doesn't say what
    kind of foreclosure, so the article never says her client bought a HUD home.
  - Facts all from hudhomestore.gov and its FAQ (read in full as a browser): where HUD
    homes come from, the Lottery / Exclusive / Extended periods, who counts as an
    owner-occupant, bidding through a HUD-registered broker, repair codes IN / IE / UI,
    and Good Neighbor Next Door.
  - Left out: Fannie Mae HomePath and "First Look" (every Fannie Mae page answered 403),
    and "as is" as HUD's words (not in the pages read).
  - Two new photos: B27, an old home being restored (body), and B28, keys handed over
    (tile). Bold cover with her line "Behind every closing is a lot of strategy, patience,
    and teamwork."; square art.
  - Episode: 794 words, 271 s, levelled (-19.6 LUFS, peak -2.0 dB).
- **Podcast episode 6** (relocating to Metro Atlanta) handed over. Its dated lines were
  re-checked today: Census Reporter still shows ACS 2024 (32.4 minutes), and no pollen
  count newer than the 14,801 record of March 2025 was found.

**Failed, and what was done**
- **The script said "a H U D home".** That reads wrongly aloud, so it was changed to
  "an H U D" before recording. Lesson added to CLAUDE.md.
- **The research row for the Lottery period lacked the quote** for "insured single-unit
  homes in a revitalization area". The FAQ was re-read and the quote added.

**Passed**
- `check-article.mjs`: ALL PASS locally (whole blog) and live. Uploads byte-identical.
- The full transcript matches the script.

**Open**
- Erika to read it.

---

## 2026-10-16 — The perfect Atlanta weekend article; podcast episode 5 hand-over

**Asked.** "Post a new blog for Erika for today, and give me the next episode."

**Done**
- **Topic choice.** Two candidates were dropped first:
  - her 50-year-mortgage video: it was only ever floated, and no reliable source shows
    whether it exists now;
  - her ground-lease reel: only commercial explainers were readable, and the Georgia
    statute site answered 403.
- **Article** `/blog/the-perfect-atlanta-weekend` (`/weekend`, Lifestyle).
  - Built from her unused Atlanta-weekend material: four videos and four reels.
  - Only year-round places are kept: Ponce City Market and its roof, Buckhead home tours,
    brunch and the BeltLine, the skyline, and Sunday in Madison.
  - Her dated picks are left out (restaurant week, Smorgasburg, a Braves game, one-off
    events). Phipps Plaza's Screen on the Green is kept in her words, with "check the
    current schedule".
  - Facts from the Beltline (22-mile loop, 45 neighborhoods), Ponce City Market ("10 steps
    from the beltline") and Visit Madison ("just an hour east", one of Georgia's largest
    historic districts).
  - Two new photos: B25, a graffiti underpass (body); B26, the SkyView wheel at sunset
    (tile). Bold cover with her line "Lord knows we have plenty of rooftops in Atlanta.";
    square art.
  - Episode: 618 words, 232 s, levelled.
- **Podcast episode 5** (homestead) handed over, with its sources in the description. Its
  ★ dates (1 April 2027; the 2026 HOME Act) are still ahead.

**Failed, and what was done**
- **Invented lines in the first draft** were removed: "the version I'd hand a friend",
  "what you're really buying… the life around it", "a whole afternoon of inspiration", and
  in the script "Here's something I believe" and "that's a Friday".
- **The full-file transcript heard "twenty-mile"**. Checked alone with `--at 115`, the
  voice says "twenty-two-mile"; it was the transcriber. Lesson added to CLAUDE.md.

**Passed**
- `check-article.mjs`: ALL PASS locally and live. Uploads byte-identical.

**Open**
- Erika to read it.

---

## 2026-10-15 — The trades / Path to Pro article; podcast episode 4 hand-over

**Asked.** "Please publish a new blog and give me episode 4."

**Done**
- **Article** `/blog/not-everyone-needs-a-degree` (`/trades`, Lifestyle).
  - Built around her four-part Home Depot series (`home-depot-part-1`, `-part-2-v2`,
    `-part-3`, `trades-vs-4-year-college`), unused until now.
  - **Re-checked against today:** Path to Pro is still running (Home Depot's releases of
    10 Mar and 17 Sep 2026).
  - What it covers:
    - the free online Skills Program, with 200,000+ graduates;
    - the Path to Pro Network, with nearly 165,000 candidates;
    - 70,000+ people certified through nonprofit partners;
    - her Atlanta-airport story;
    - BLS pay for electricians, plumbers and HVAC (May 2025), openings and training;
    - Georgia's HOPE Career Grant ("100%" of tuition, per TCSG);
    - her business and parent lines.
  - **Her "$75k to $150k" is quoted as hers**, beside the BLS medians ($61k–64k). The rule
    "show both" is now in CLAUDE.md.
  - Two new photos: B23, an electrician stripping wire (body); B24, an HVAC technician
    (cover tile). Bold cover; square art.
  - Episode: 953 words, 364 s, levelled. The full transcript matched the script.
- **Podcast episode 4** (cash is king) handed over. Its text has no dated lines.

**Failed, and what was done**
- **Invented lines in the first draft** ("the line I keep coming back to", "the part I
  care about most", and a real-estate claim she never made) were replaced with her actual
  words before recording ("entrepreneurship is my jam", "I work with these service
  providers on a constant basis").
- Left out: which trades are on the HOPE Career Grant list (sources disagree: 12, 17 or
  18 fields) and Georgia's licensing rules (sos.ga.gov answers 403).

**Passed**
- `check-article.mjs`: ALL PASS locally and live. BLS answers 403 to the checker (WARN);
  it was read with WebFetch.
- Uploads byte-identical live.

**Open**
- Erika to read it, especially the pay figures beside her own.

---

## 2026-10-14 — The Wall Street homebuying-ban article; podcast episode 3 hand-over

**Asked.** "Publish a new blog for Erika and give me the next podcast to publish."

**Done**
- **Article** `/blog/wall-street-homebuying-ban-explained` (`/wall-street`, Buying).
  - Built around her unused video `banning-wall-street-homebuying`. In it she described a
    proposal; it was **re-checked against today**. It became law on 11 July 2026 as
    Section 1001 of the 21st Century ROAD to Housing Act (H.R. 6644), read in the
    enrolled text on govinfo. The purchase ban starts **7 January 2027**.
  - It covers the law (350+ homes; two units or fewer; $1M or 3× penalties; no forced
    sales; the exceptions) and the Atlanta angle: GAO estimated investors with more than
    1,000 homes held about 25% of Atlanta's single-family rental market as of 2022.
  - It gives her prediction and the Urban Institute's caution side by side.
  - Two new photos: B21, a row of new homes by a pond; B22, a Wall St sign (cover tile).
    Bold cover with her line "Wall Street could be your next-door neighbor."; square art.
  - Episode: 995-word script, 370 s, levelled (-19.9 LUFS).
- **Podcast episode 3** (know your house before you list) handed over with its MP3, art,
  title, description, keywords and fields. Its text has no dated lines.

**Failed, and what was done**
- **My draft gave her feelings and opinions she never stated**: "and I love this", "My
  honest read", and "a title I think says it all" in the script. All removed before
  recording.
- **The voice garbled "HUD"**: the transcript heard "SHUDI". The line now says "the
  Department of Housing and Urban Development". After re-recording, the full transcript
  matches the script. The new take is faster (370 s, was 405).
- **The cover's WebP versions were missing on the first deploy**: `build-images.php` had
  run before the cover was rendered. Rebuilt and uploaded. CLAUDE.md step 5 now says to
  run it again after the cover.

**Passed**
- `check-article.mjs`: ALL PASS locally and live. GAO answers 403 to the checker (WARN);
  it was read with WebFetch.
- Uploads byte-identical live.

- **GitHub refused the push**: the rebuilt site zip was 102.9 MB, over its 100 MB file
  limit. The unpushed zip commit was undone locally, and the article commit was pushed
  without it. The zip is no longer committed; build it on request (CLAUDE.md step 7).

**Open**
- Erika to read the article. ★ Re-check it when Treasury issues regulations, and after
  7 Jan 2027.

---

## 2026-10-13 — Fannie Mae 620 article (NEXA #2), episode 2 hand-over, freshness fix

**Asked.**
- "Post a new blog for Erika's website."
- "Give me the details for the 2nd episode": the title, description, keywords and other
  fields.
- Mid-task: "make sure anything published is relevant for the current time".

**Done**
- **Article** `/blog/fannie-mae-dropped-the-620-minimum` (`/credit-score`, Buying), **NEXA
  / Andrew series #2**, which completes October (2 of 2).
  - Built around her unused "Fannie Mae" video (November 2025), re-checked first against
    the current Selling Guide (published 7 Oct 2026). The Guide now says: "A minimum
    credit score is not required for DU loan casefiles."
  - It also covers what didn't change: 620/640 for manual underwriting, 620 for
    government loans, and the lender still makes the loan.
  - What DU weighs; rent history ($300+, 12 months, bank-statement data); utilities as
    nontraditional credit when there's no score; the CFPB steps for free reports and
    disputes.
  - Her lines are quoted from her two public videos only. Her private mentoring-call
    method was left out.
  - Disclosure line with both NMLS numbers; no rates.
- **Pictures:** two new Pexels photos (B19 a magnifier over a bank statement, B20 bills
  beside a laptop). Bold cover with her line "Fannie Mae just hit the reset."; square
  podcast art.
- **Episode:** 1,111-word script, 409 s, levelled. The full transcript matched the script.
- **Episode 2** (owning vs investing): MP3, art, title, description, keywords and field
  settings handed over.
- **Deployed** the article and **all 12 levelled MP3s** (the 11 from 12 Oct were still the
  uneven originals live). 26 files, each size-matched, and the MP3s and pictures
  byte-identical live.
- **Freshness audit** of every published article for "right now", "most recent", "this
  year" and similar.
  - One real problem: closing costs (episode 1) said "Where Metro Atlanta sits right
    now… the most recent… brief, covering July 2026". It now says "stood this summer" and
    "for July 2026", plus "Markets move month to month…".
  - Its `audio_note` dates the recording (29 Sep) and the figures (July 2026). Deployed
    and confirmed live.
  - Everything else is either dated in the text or still current.

**Failed, and what was done**
- **`level-audio.py` let a peak through at 0.0 dBTP** on the new episode: the 5 ms limiter
  was too slow. It now uses a 1 ms attack and steps the ceiling down (0.708, 0.6, 0.5)
  until the encoded file measures under -1.2 dBTP. The episode passed at -1.2.
- **georgia.gov and dor.georgia.gov answered 429** (rate limiting) after several full
  check runs, so 3 articles failed "every source link answers". The same pages gave 200
  one at a time. `check-article.mjs` now pauses, retries a 429 once as a browser, and
  warns instead of failing.
- The transcriber writes "Axon Realty" for Axen; it's the same sound, like "Erica".

**Passed**
- New article: `check-article.mjs` ALL PASS, locally and live.
- Whole blog, live: every episode levelled (-19.4 to -19.7 LUFS). After the 429 change,
  the three rate-limited articles pass too, with warnings only.

**Open**
- Episode 1's RSS.com description should add "Recorded September 29, 2026; the market
  figures are from July 2026."
- Erika and Andrew to read the new article.

---

## 2026-10-12 (night) — Podcast episode art, and even volume on every episode

**Asked.** The owner started publishing the podcast on RSS.com with episode 1 (closing
costs). RSS.com refused the art: "Minimum resolution required is 1024 x 1024 px". "Make
it, and add this to the notes." Also: "verify all voices have the same volume; at some
point the volume changes, why?"

**Found**
- The episode art was the 1600×1000 article cover. Podcast art must be square.
- **The volume really did vary.** Nothing levelled the narrations:
  - between episodes it ranged from -38 LUFS (homestead, cash is king) to -17.5 (closing
    costs), a 20 dB spread;
  - inside episodes too. In the bank-vs-broker episode the voice sat around -42, jumped to
    -18 at the chunk seam (2:15), then drifted back down to -40. The restaurant and
    relocation episodes swung about 15 dB.
  - **Why:** ElevenLabs returns each ~2,300-character chunk at its own level, and the level
    drifts inside a chunk too. `narrate.py` joined the chunks as they came.

**Done**
- `tools/covers/blog.js --podcast`: a square **Bold** template, 1400×1400, with "Erika
  Explains" at the top. Art for all 11 episodes is in `tools/podcast/art/`.
- `tools/level-audio.py`: two passes of loudnorm in dynamic mode ride the drift, then a
  measured linear pass to **-19 LUFS** (mono podcast target), a -3 dBFS limiter (MP3
  encoding pushed two files above 0 dBTP without it), and 64 kbps mono. `--check` measures.
- All 11 MP3s were levelled. `narrate.py` now levels every new episode, and
  `check-article.mjs` fails an article whose audio isn't levelled.

**Passed**
- All 11: -19.4 to -19.7 LUFS, true peak -1.2 to -2.9 dB, every 10-second stretch within
  2.7 dB.
- Durations are unchanged (each `audio_secs` still matches) and the ID3 tags were kept.
- The noise floor after the gain is about -70 dBFS (inaudible).
- `check-article.mjs --all` locally: ALL PASS. The new check fails the old bank file
  (14.8 dB spread).

**Open**
- **Deploy the levelled MP3s**: no cPanel keys in this part of the session, so the live
  site still plays the uneven originals.
- Hand over episodes 2–11 with their square art as the owner publishes.

---

## 2026-10-12 (later still) — Full copy of escaluxe.com (property management site)

**Asked.** "Make the whole copy of Erika's property management website, as it is":
https://escaluxe.com/. The owner chose to rebuild the Canva contact form so its answers
are emailed through the VPS.

**Found**
- A Canva website export (`<base href="/">`) with **6 pages**, taken from its page list:
  `/`, `/services`, `/faq`, `/faq-2` (What We Don't Do), `/contact`, and
  `/government-backed--housing-authority-rental-programs` (Section 8 & Voucher Programs).
  Every page's HTML carries all six designs; only the title differs.
- The Contact page holds a **Canva form widget** (`/_website-element-widget`). It loads
  Canva's app code and fonts and posts to `www.canva.com/_ajax/form/`, so it can't work
  off Canva.

**Done**
- **Capture:** `capture.mjs` now takes a list of pages; all 6 captured at desktop and
  phone.
- **Sweep:** `sweep.py` reads the HTML and CSS for every `_assets/` and `url()` file
  and downloaded the 139 files the browser hadn't asked for (other font formats and
  photo sizes), with 0 failures. The site is 33 MB, byte-identical to Canva's.
- **Rebuilt the contact form:** same fields and look, set in DM Sans. Canva's form used
  Hagrid, a font Canva licenses, so it wasn't copied.
  - `send.php` emails each message over SMTP (PHPMailer) with Reply-To set to the
    visitor.
  - It validates the fields and has a honeypot, a too-fast check, a 5-per-10-minutes
    limit per IP, and header-injection guards.
  - `config.sample.php` holds the SMTP settings; `.htaccess` files block the private
    files.
- **`exports/escaluxe-property-management/`** and its zip (33 MB): `site/` (the web root),
  `README.txt` (form setup, nginx/Apache), `FILES.txt`, and `screenshots/`.
- **Tools:** `build-site.py`, `sweep.py`, `verify-site.mjs` and the form source
  `escaluxe-form/` are in `tools/canva-export/`. Building from the repo tools reproduces
  the delivered site exactly.

**Passed**
- Offline (outside network blocked): **all 6 pages, desktop and phone: 0 failed and 0
  outside requests**, form included.
- Live vs copy, full-length: **9 of 12 views at 0.00%**.
  - Contact desktop differs by 3.5%; that's the rebuilt form.
  - FAQ and Contact on phone: the live measurement didn't find the scroller. The first
    screen matches at 0.00%.
- Menu navigation offline: every link opens the right page and title.
- Form, end to end with a local SMTP catcher:
  - validation messages, and the email delivered with the right To, From, Reply-To and
    Subject;
  - the honeypot and too-fast submissions are silently dropped;
  - injection is refused, and the 4th message in 10 minutes gets a 429.
- The zip unpacks and serves from a fresh folder. With no `config.php`, the form says
  it isn't set up yet.

**Failed, and what was done**
- **Chromium crashed at launch (SIGTRAP)** when `TMPDIR` pointed at the long scratchpad
  path. Leave `TMPDIR` alone.
- **Canva pages scroll inside one DIV**, so `fullPage` screenshots show one screen.
  `verify-site.mjs` scrolls that DIV (for lazy photos), expands it, then shoots.

**Open**
- **The owner sets up `config.php`** (recipient plus the SMTP mailbox), then sends a
  test message.
- The live site sends `x-robots-tag: none`; decide on indexing on the VPS.
- "FOR HOMEOWNERS &amp; BUSY PROFESSIONALS" on the FAQ is in Canva's original. Fix it in
  Canva.

---

## 2026-10-12 (later) — Downloadable copy of the Canva page escaluxeglobal.com/ai-clone-video-services

**Asked.** Make a downloadable copy of Erika's Canva page, to host on their own VPS later.

**Found**
- The page is a Canva "export_website" build (12 KB of HTML and Canva's JavaScript
  runtime).
- All its content is one **Canva Code widget** (a "codelet") in an iframe: a
  self-contained HTML page built on Tailwind, Lucide and Google Fonts.

**Done**
- **Captured everything the page loads,** in headless Chromium at desktop and phone
  width. Each request was relayed through curl with TLS verified.
- **Built `exports/escaluxe-ai-clone-video-services/`** and its zip (4.2 MB, not in
  git):
  - `index.html`: the widget as a plain page. Canva's three hosting scripts were removed
    (telemetry, data and element SDKs; the page doesn't use them). Tailwind, Lucide and
    all 49 Google Font files are local in `vendor/`.
  - `canva-original/`: Canva's wrapper, byte-identical, as a backup.
  - `README.txt`: what's what, nginx/Apache hosting, the page's outside links.
  - `FILES.txt`: checksums.
- Tools kept in `tools/canva-export/` (`capture.mjs`, `build.py`, `verify.mjs`).

**Passed**
- Offline check (all outside network blocked): the standalone page made 0 failed
  requests and 0 outside requests, on desktop and phone. All 8 fonts loaded and every
  section rendered.
- Screenshot diff against the live page: 0.00% on desktop for both copies, and 0.00% on
  phone for the backup.
- The zip unpacks and serves from a fresh folder.

**Failed, and what was done**
- **The widget answered 404 to the capture.** Canva's codelet needs the browser's
  `Sec-Fetch-*` iframe headers. Chromium adds those after Playwright intercepts the
  request, so the relay now rebuilds them.
- **The widget's scripts went to the wrong host.** The codelet redirects to a signed
  `<id>.canvacode.com` URL, and its root-relative scripts then hit escaluxeglobal.com.
  The relay now gives a redirected HTML page a `<base>` at its final URL. (Handing the
  redirect to the browser doesn't work: Playwright doesn't route the follow-up request.)
- **Long signed tokens broke the file paths** (ENAMETOOLONG); they're shortened to hashes.
- **The backup's widget couldn't reach `vendor/`.** Canva's absolute `<base href>` means
  the backup needs its own copy of the libraries.

**Differences, and left as-is**
- On phones, Canva scales its frame down, so the standalone page shows the design's own
  mobile layout at full size (14.9% of pixels differ; same content).
- An SVG `<polyline>` with a missing number is in Canva's original markup too.

**Open**
- The owner hosts it on the VPS.
- The page's links (Calendly, email, phone, escaluxeglobal.com) are unchanged; recheck
  them once escaluxeglobal.com moves.

---

## 2026-10-12 — "When stairs become the enemy."

**Asked.** "Please research and post a new blog for Erika's website!"

**Picked.** Her unused short video `stairs-hidden-enemy`. The NEXA / Andrew series stays
at **1 of 2 for October**; the second is spaced for later in the month.

**Done**
- **Research, all official:**
  - CDC: older-adult falls data, and the *Check for Safety* checklist (2018).
  - National Institute on Aging: aging in place.
  - IRS Topic 701: the home-sale exclusion.
  - Georgia DOR: senior homestead exemptions.
- **Article** `/blog/when-stairs-become-the-enemy` (Lifestyle, short `/stairs`, ~840
  words, FAQ of 4, 6 sources).
  - Her lines open and close it.
  - Her dated "five coming to market" line was left out.
  - Three lines that gave her intent or opinions she never voiced were cut before
    publishing.
- **Photos:** a staircase with a handrail on one side and a wall light (HONG SON, B17);
  a single-story brick ranch home (Get Lost Mike, B18). **Bold cover** C11.
- **Podcast** (805 words, 279 s).
  - Re-reading the script found two slips: "I love that" (a feeling she never stated) and
    "here's one a lot of homes miss" (no source). Both were cut before recording.
  - Whole-file listen-back matched.

**Passed**
- `check-article.mjs --all`: ALL PASS on 11 articles, locally and live.
- 13 uploads byte-identical.

**Failed, and what was done**
- **The link check failed on NIH (405).** NIH answers 405 to the checker's user agent
  "Mozilla/5.0 (link check)" and 200 to a browser string. `check-article.mjs` now retries
  a 403/405 once as a browser, and a refusal that persists is a WARN, like 403.
- **CDC refuses curl even with a browser UA (403).** WebFetch can still read its PDFs: it
  saves the binary under `tool-results/`, which pypdf reads. Noted in `CLAUDE.md`.

**Open**
- Erika to review the article and its episode.
- **NEXA / Andrew: 1 of 2 for October.** One more is due by 31 Oct.

---

## 2026-10-10 (later) — "Same tax rate, so why a tax increase?" (the rollback rate)

**Asked.** "Please publish a new blog for today as well."

**Picked.** The strongest unused material in `ceo_erika`: her Gwinnett rollback-rate
reel `Dbs_B2MBThx`. Its homestead half was answered on 5 Oct; the rollback half was
still unused. This article isn't part of the NEXA / Andrew series, which stays at **1 of
2 for October**.

**Done**
- **Research, all official.**
  - Gwinnett's 2026 sources: the legal *Notice of Property Tax Increase* (PDF), the
    14 July and 4 August releases, and the assessors' "how to read your notice" guide
    (PDF).
  - Georgia DOR: the Property Taxpayer's Bill of Rights (O.C.G.A. 48-5-32.1), the
    millage-rates page (a mill, the 40% ratio), and form PT-306E (estimated roll-back
    rate under 48-5-306).
- **Conflict resolved at the source.** Gwinnett's rollback rate was 6.792 in the July
  release and 6.782 on its comment page. The legal notice says "no more than 6.792
  mills", and 6.950 − 0.158 = 6.792.
- **Article** `/blog/the-rollback-rate-explained` (Buying, short `/rollback`, dated
  10 Oct, ~940 words, FAQ of 4, 8 sources). It links to the homestead article.
- **Photos:** aerial suburban street (Alex Hostetler, B15, recompressed to 249 KB);
  "PAID / DUE" bills (Tara Winstead, B16). **Bold cover** C10, with the tile's crop moved
  onto the notes.
- **Podcast** (814 words, 318 s): the whole-file listen-back matched, all figures right.

**Passed**
- `check-article.mjs --all`: ALL PASS on 10 articles, locally and live.
- 13 uploads byte-identical.

**Failed, and what was done**
- **A drafting slip got into the podcast script:** "so working people can come.
  Hmm... actually, let me just say what the law says." The reason it gave is in no
  source. Caught when re-reading, before recording, and removed, along with "comes in
  the spring", which no source states. Lesson added to `CLAUDE.md`.
- **The cover tile showed blank paper.** Its crop was moved onto the notes; the subline
  is now her own words only.

**Open**
- Erika to review the article and its script.
- **NEXA / Andrew: 1 of 2 for October.** One more is due by 31 Oct.

---

## 2026-10-10 — NEXA / Andrew series #1: "Your bank is not your best option for a mortgage"

**Asked.** Publish an article that refers Erika's lender partner Andrew and NEXA ("to get
some benefits from Nexa"), about twice a month from now on. Keep that in memory, and
remind the owner every day a blog is published. Mid-task the owner sent Andrew's email
signature.

**Done**
- **Research.**
  - Andrew: Andrew "Drew" Channell, Senior Loan Officer, NMLS #1920676; NEXA company
    NMLS #1660690. From his signature, and consistent with Missouri licensing records seen
    in search. NMLS Consumer Access blocks automated requests (Cloudflare 403), so it
    wasn't read directly.
  - NEXA: renamed NEXA Lending in October 2025 and now funds about 60% of its loans itself
    (HousingWire).
  - CFPB: lender vs broker, how brokers are paid, credit checks while shopping (45 days).
  - Regulation X §1024.14 and §1024.15, read for the owner.
- **Article** `/blog/your-bank-is-not-your-best-option-for-a-mortgage` (Buying, short
  `/lender`, dated 10 Oct, ~850 words, FAQ of 4, Sources).
  - Built on "Drew's Video" and her "convince a stranger" video, both unused before. Her
    claims about him are attributed to her video.
  - The call to action goes through her ("get with me so you can get with Andrew").
  - Disclosure line with both NMLS numbers.
- **Two new photos:** couple reviewing loan documents (Ron Lach, B13); "Personal
  Banking" sign (Erik Mclean, B14). **Bold cover** C9.
- **Podcast** (824 words, 294 s): the whole-file listen-back matched the script.
- **Series tracking.**
  - `tools/series/nexa-andrew.tsv` lists the series articles.
  - The SessionStart hook prints "This month: N of 2" and a reminder until two are done.
  - `CLAUDE.md` has a new section, *NEXA / Andrew series*, with the rules and unused
    topics.

**Passed**
- `check-article.mjs --all`: ALL PASS on 9 articles, locally and live.
- 13 uploads byte-identical.

**Failed, and what was done**
- **Every page failed "real, complete page" locally.** The container had restarted, so
  the PHP server wasn't running. Restarted it and everything passed. Lesson added to
  `CLAUDE.md`.
- **Draft lines she never said were cut:** "I meant every word", "I recommend Andrew
  because", and "you're never required to use…" (the last stays in the disclaimer only).

**Open, for the owner**
- **Axen Realty and NEXA share an address**: 5559 Sossaman Rd #101, Mesa (his signature,
  and Arizona's licence record for AXEN REALTY, LLC). If they share ownership, referrals
  need an Affiliated Business Arrangement disclosure.
- **Any "benefit" from NEXA for referrals is a RESPA §8 question.** Clear it with Axen's
  broker or an attorney first.
- **Andrew and Erika should read the article.** Should his phone and email go on the site?
- **One more NEXA / Andrew article is due in October.**

---

## 2026-10-09 — The voice is back: three episodes, and "The open house is a sales event"

**Asked.** "Write a new blog for today and try the podcasts today as well." The owner
picked the open-house topic.

**Found.** Erika's ElevenLabs voice works again. A read-only check showed it fine-tuned
on every model, including `eleven_multilingual_v2`. The outage ran 6–7 Oct.

**Done**
- **Two waiting episodes recorded** from their scripts with the usual podcast voice
  settings: relocation (342 s) and restaurant lease (453 s). `audio` and `audio_secs`
  set; their stale "not recorded yet" notes updated.
- **New researched article** `/blog/the-open-house-is-a-sales-event` (Selling, short
  link `/open-house`, ~1,030 words, FAQ of 4, Sources).
  - Built on her reel with Evelyn (`Dbt77Dkuk44`), plus two lines from the Smyrna tour
    (`DbziGayiEgQ`).
  - **The transcript has no speaker labels.** Read closely, "Know who's there for the
    right reason" and "Everybody's welcome. But this is a sales event" are **Evelyn's**
    words, describing what she learned from Erika ("And she taught me that"). They are
    credited to Evelyn. The caption is Erika's.
  - Researched: NAR open-house guidance, NAR's privacy-and-safety guide and seller
    checklist, HUD and DOJ on the Fair Housing Act, NAR's 2025 Profile (88% of buyers
    and 91% of sellers used an agent). Notes in
    `tools/research/the-open-house-is-a-sales-event.md`.
- **Two new Pexels photos**, looked at on contact sheets, cropped 1600×1000 from 2400 px:
  - agent welcoming a visitor at the door (RDNE Stock project, B11, body);
  - staged open-plan living room (Curtis Adams, B12, tile).
- **Bold cover** (C8); looked at full size and at card size.
- **Podcast script** (1,072 words) recorded as a 404 s episode.

**Passed**
- Listen-back of the whole open-house episode: word for word against the script,
  including the seams, the seven protected classes and the 88% / 91% figures.
- `check-article.mjs --all`: ALL PASS on 8 articles, locally and live. The three old
  WARNs are sites that refuse non-browsers.
- Every upload byte-compared with the live copy (15 files).
- Key grep: none of the four keys is in the history, the tree or the zip.

**Failed, and what was done**
- **The "where buyers found their home" figure couldn't be verified.** Search gave
  52% (NAR) and 46% (a local association's blog). No readable NAR page states either:
  the press release and the highlights page don't, and the full report is sold. Left
  out, and the article uses the agent-usage figures the press release does state.
- **`stt-check.py --head 0` transcribed the whole file (by accident), and `--tail 0`
  crashed** (ffmpeg rejects `-sseof -0`); 15 windowed calls were made where one would
  do. Fixed: a new `--full` flag transcribes the whole episode in one call, and a 0 for
  `--head` or `--tail` now skips that cut.
- **NAR's *Safe Selling* line** ("very low incidence of violent crime… very high
  incidence of theft") was only seen quoted second-hand, so it was dropped.

**Open**
- Erika to review the open-house article and the Evelyn attribution. Evelyn may want to
  approve being quoted by name.
- Rotate all keys.


---

## 2026-10-07 (later) — Every article gets its own pictures

**Asked.** "Why are you using the same picture in every blog, it's soooo lazy." The owner
sent Pexels and Pixabay keys.

**Found.** Four pictures were shared:
- the closing-table signing photo was the body picture **and** the cover tile on both the cash-is-king and restaurant-lease articles (4 uses);
- the suburban home for sale was in closing costs and homestead;
- the loan paperwork was in closing costs and on the homestead tile;
- the skyline was both the relocation body picture and its tile.

The cause: `CLAUDE.md` step 4 said to reuse "free" library photos, and there was no stock key.

**Done**
- **Six new Pexels photos**, each looked at on a contact sheet, cropped to 1600×1000 (no upscaling, all from 2400 px originals), credited and registered as 17/B5–B10:
  - restaurant kitchen with hoods (Maria Orlova) — restaurant body;
  - empty commercial building with parking (Erik Mclean) — restaurant tile;
  - keys handover (Alena Darmel) — cash tile;
  - Southern porch home (Curtis Adams) — homestead body;
  - US mailbox, flag up (Abstrakt Xxcellence Studios) — homestead tile;
  - moving boxes (Ivan S) — relocation tile.
- **Four covers re-rendered and looked at;** their `cover_credit` lines updated.
- **`check-article.mjs`** has a new blog-wide check: no picture on two articles, and no tile repeating its own body picture.
- **`tools/stock-search.py`** (new): Pexels search with a numbered contact sheet.
- **`CLAUDE.md`** step 4 is rewritten; checks-table row added; the new keys added to the rotate list.

**Passed**
- The new check FAILS on the old `posts.php` and `blog.json` (it lists exactly the repeats above) and PASSES on the new ones.
- `check-article.mjs --all` passes, 7 articles.

**Failed, and what was done**
- **Porch photo too large.** It came out at 458 KB; recompressed to 279 KB.

**Open**
- The audio for two episodes still waits on the voice.
- Rotate all keys.

---

## 2026-10-07 — Researched article: leasing a restaurant space (audio still blocked)

**Asked.** "Write a new blog for today please!" The owner picked the restaurant-lease
topic, and chose to publish now and add the audio later, because the voice is still down.

**Done**
- **Article.** *Leasing a restaurant space? Fall in love with the deal, not the space.* (`/blog/leasing-a-restaurant-space`, short link `/restaurant-space`, Investing).
  - Built on her nine restaurant-space-hunt reels (`DdFheexBDEJ`, `DdIKP_lOOjV`, `DdK91SLt184`, `DdPn3i0M6RP`, `DcoiCRJiiR6`, `DdAY1ocMvyI`, `Dc2ShAQh5mR`, `DdC6wYEsE35`, `DdM6HMcMsW2`).
  - Her checklist and lines are quoted: zoning, parking, the back door, traffic, neighbors, cheap vs affordable, and the lease terms she negotiates.
  - The permits section is sourced from:
    - the Fulton Board of Health brochure (plan review before construction, permits don't transfer, business/liquor/zoning approvals);
    - the GNR plan-review checklist (grease trap on the plumbing plans);
    - Atlanta Watershed (grease permit);
    - the Gwinnett Fire CO leaflet;
    - GDOT's TADA traffic counts;
    - the SBA (zoning).
  - Notes in `tools/research/leasing-a-restaurant-space.md`.
  - Script (1,218 words) in `tools/podcast/leasing-a-restaurant-space.txt`.
- **Cover.** Bold, with the signing photo as the reference tile.
- **Deploy.** 6 files, 0 failures. `check-article.mjs --all`: ALL PASS locally and live, 7 articles.

**Failed, and what was done**
- **Voice.** Still "voice_not_fine_tuned" this morning and again at recording time. The article is published without audio.
- **Sites blocked.** atlantaga.gov, dph.georgia.gov and Atlanta's document server refused every request.
  - Official PDFs from the county sites downloaded fine with a browser user-agent and were read with the venv's pypdf. The method is in `CLAUDE.md`.
  - The City of Atlanta's certificate-of-occupancy rules couldn't be read, so Gwinnett's is used as the example.
  - WABE started answering 403 (it was 200 on 5 Oct); it's a WARN only, since that page was read and recorded then.
- **Embellishments cut.** In the article: "No kitchen. Nothing." and a "posted redevelopment plan right on the corner". In the script: "out and in".
- **Dated source.** The Gwinnett fire leaflet is from 2012. It's starred and used only as "for example".

**Open**
- Two episodes wait on the voice: relocation and restaurant (homestead is already recorded).
- Erika to review the article.
- Rotate the keys.

---

## 2026-10-06 — Researched article: moving to Metro Atlanta (audio blocked)

**Asked.** "Let's post a new blog today as well!" The owner picked the topic,
relocating to Metro Atlanta, over the recommended restaurant-lease one.

**Done**
- **Article.** *Moving to Metro Atlanta? What I wish I'd known first.* (`/blog/relocating-to-metro-atlanta`, short link `/moving-to-atlanta`, Lifestyle).
  - Built on her video `what-i-wish-i-knew-about-relocating` and reel `DayobmnQJdl`: her four points (not one city, traffic, pollen, opportunity) in her own words.
  - Researched facts around them, from:
    - BLS (29-county metro);
    - Census ACS 2024 via Census Reporter (32.4-minute commute);
    - GDOT's MARTA profile;
    - the city school systems and the state report cards;
    - Atlanta Allergy & Asthma, and Atlanta News First (record 14,801 on 29 Mar 2025);
    - DDS (licence within 30 days), DOR (car within 30 days, 3% title tax) and georgia.gov (automatic voter registration).
  - Links to the homestead article, the Metro Atlanta page and contact.
  - Notes in `tools/research/relocating-to-metro-atlanta.md`; script in `tools/podcast/relocating-to-metro-atlanta.txt`.
- **Photo.** The library's wide dusk skyline cropped to 1.6 (1440×900, no upscaling) and registered as 17/B4. Bold cover.
- **Deploy.** 10 files, 0 failures. `check-article.mjs --all`: ALL PASS locally and live, 6 articles.

**Failed, and what was done**
- **ElevenLabs refused Erika's voice.** It answered "voice_not_fine_tuned" ("is not fine-tuned and cannot be used"), a day after it worked.
  - The voice is still listed but has no fine-tuned model, and `is_allowed_to_fine_tune` is false. The key can't read the subscription (no `user_read`).
  - Published without audio, rather than using another voice or making a new clone.
  - `narrate.py` now stops on that error at once with a plain message instead of retrying 4×.
- **Facts dropped.** Three couldn't be verified (every source blocked or empty): the statewide count of school districts and city systems, the previous pollen record (reports disagree), and the regional commuter buses. Listed in the research notes.
- **Invented lines caught in the draft.** "That is why I always say…" (a habit she never stated) and "Cars, porches, sidewalks" (an embellishment) were cut. In the script, "I did a whole episode on homestead" assumed it had been uploaded; reworded to point to her website.
- **The script came out at 866 words, about 5 minutes,** short of 6–8. Noted in `CLAUDE.md`; aim for ~1,100 words.

**Open**
- The owner fixes the voice in ElevenLabs, then the episode is recorded and deployed.
- Erika to review the article.
- Rotate the keys (still active).

---

## 2026-10-05 — First researched article, with a podcast-style episode

**Asked.** A new article built from research with references from reputable sources,
not an old video transcript. Its ElevenLabs audio should sound like a real podcast,
someone talking rather than a page being narrated. Save the method in the repos for the
next article.

**Done**
- **Article.** *Georgia's homestead exemption, explained, and why 2027 matters* (`/blog/georgia-homestead-exemption-explained`, short link `/homestead`, Buying).
  - Sources: georgia.gov; the Department of Revenue page and its Bulletin 2025-01; SB 33 (the HOME Act) as signed; WABE; Tax Foundation; GBPI; GMA; Ownwell, attributed; the Fulton, DeKalb, Cobb and Gwinnett tax offices.
  - Inline links, a Sources list at the end, and a not-advice line.
  - It opens with her own reel `Dbs_B2MBThx`, which promised this video.
  - Every claim is recorded with the words it rests on in `tools/research/<slug>.md`.
- **Podcast episode.** A separate spoken script, `tools/podcast/<slug>.txt`, about 1,100 words.
  - `narrate.py --script` with conversational voice settings kept in the script (stability 0.38, style 0.22).
  - 390 s, 3 chunks.
- **`audio_note`.** A new post field, so the page no longer says "same words, read aloud" under an episode. It is carried through `blog_clean`, the admin form, save and new-draft, and the page.
- **Checker.** New checks: every source link answers (403 is a warning); the line under the player is true.
- **Deploy.** 10 files, 0 failures, size-matched.

**Passed**
- **Listen-back.** The transcript at 0–50 s, 131 s (seam), 165, 192, 220, 264 s (seam), 300, 335 s and the ending matches the script word for word. "House Bill five eighty-one", "Senate Bill thirty-three", "May eleventh, twenty twenty-six", "sixty-eight percent" and "April first, twenty twenty-seven" all came through right.
- **Admin round-trip.** Run on a throwaway copy with a test login: after a real Save, `audio_note` and every `short` survive.
- **Checks.** `check-article.mjs --all` passes locally and live, for 5 articles.

**Failed, and what was done**
- **Sources disagreed.** A search snippet ("Georgia rejects property tax caps") contradicted others ("SB 33 signed"). Reading the pages showed broader caps failed and SB 33 passed. The 2027 start isn't in the bill text, so the article attributes it ("analyses of the law expect…") and says to confirm with your county.
- **PDF tools.** There's no poppler, and `pypdf` crashed on the system `cryptography`. Text was extracted in a scratch venv; the procedure is now in `CLAUDE.md`.
- **Fulton's link.** The assessor site returns 403 to every non-browser request (curl and WebFetch). It was verified by a domain search instead and is a WARN, not a FAIL, in the checker.
- **Invented feelings.** The first draft had Erika say "the timing is better than I planned", which she never said. It was rewritten; the rule is in `CLAUDE.md`.
- **Round-trip script.** It failed twice on selectors: the login button has no `type`, and the blog form's button is `do_blog`, not `do_save`. Fixed in the throwaway script only.

**Open**
- Erika to read the article and script.
- The podcast episode upload (copy handed over).
- Rotate the keys.

---

## 2026-10-02 (later) — Cash is king narrated, deployed and verified live

**Asked.** The owner supplied the ElevenLabs key and cPanel user and token; finish the article.

**Done**
- **Narration.** `tools/narrate.py`: 2 chunks, 231 s, 64k mono. The listen-back check covered the opening, 80 s, 106 s, the seam at about 134 s, 160 s and the ending. Every phrase came back as written, including "24 plus years", "seven to ten days", "as is", the contingency list and the closing line. `audio` and `audio_secs` are set on the post.
- **Deploy.** `tools/deploy.sh`: 12 files, 0 failures, each size-matched on the host. The files were `cms.php` (the entity fix), `posts.php`, `photos.php`, the MP3, the cover, the closing-table photo, and the WebP versions of both pictures.
- **Live.** `check-article.mjs --all` through the mirror, with `--origin https://erikakpage.com`: ALL PASS for 4 articles.
  - `/cash-is-king` answers 301.
  - The live `/blog` page no longer shows `&rsquo;`.
  - Audio length matches on every article.

**Failed, and what was done**
- **First live run.** One FAIL: the investing article's hero WebP was "not loaded". Fetching the three files directly showed them on the host and byte-identical; the request had failed once on the way. The rerun passed.
  - `check-article.mjs` now reloads the page once and looks again before reporting a picture as not loaded. The full live and local runs pass with it.

**Open**
- Podcast episode: the owner uploads it to RSS.com (copy handed over).
- The keys were pasted into chat, so rotate the ElevenLabs key and cPanel token.
- Erika to review the added caution.

---

## 2026-10-02 — "Cash is king" article; one branch; the self-updating loop

**Asked.** Publish today's article. Use the default branch for everything and merge
other branches into it. Keep the repo updated after every change: the routine, what
passes, what fails, everything learned, so each new session starts informed.

**Done**
- **Branches.** Merged the default branch (`claude/client-website-ux-ra0p3o`, the old
  static mockup) into the live branch.
  - Its two extra commits only touched `index.html`, which the PHP site had replaced, and `index.php` already had both fixes. The deletion was kept and the site is unchanged.
  - The default branch was then fast-forwarded to the live site. Both branches now point at the same commit, and the default branch is the source of truth.
- **Article.** *Cash is king, if you really mean cash* (`/blog/cash-is-king-if-you-really-mean-cash`, Buying, short link `/cash-is-king`, no guide).
  - Sources: her `cash-is-king` transcript (17 Jul 2026, ~320 words), reel `Da_k4xWCG2o` and its caption.
  - Added a general caution on giving up contingencies, flagged for Erika.
  - Body picture: the closing-table photo from the library, which no article was using.
  - Bold cover, with the supporting line from her caption.
- **Bug fixed site-wide.** `blog_clean()` passed `title`, `excerpt`, `seo_*` and `cover_alt` straight to `esc()`. Every `/blog` card and article intro therefore showed `&rsquo;` and `&mdash;` as literal text, on the live site too. Added `plain_text()` (in `cms.php`) to decode them once.
- **`tools/check-article.mjs` (new).** The article checks as one command, PASS/FAIL per line:
  - picture shapes, and the cover on every surface;
  - the offer box or "Blog enquiry" form;
  - audio file vs `audio_secs`;
  - the short-link 301, `/blog` listing and product back-link;
  - no entities shown as text.
  Until now these checks were rewritten in temp scripts every session and lost.
- **The loop.**
  - This log.
  - "The loop" and the one-branch rule in `CLAUDE.md`.
  - `.claude/settings.json` hooks: SessionStart prints branch state, the newest log entry and open items; Stop blocks once on uncommitted, unpushed or unmerged work, or a stale log.

**Passed**
- `check-article.mjs --all` locally: 4 articles, every check.
- Entity check proven both ways: it fails on the old `cms.php` (`&rsquo; &mdash;`) and passes on the fixed one.
- Hooks pipe-tested by hand: clean tree passes; dirty tree, unpushed commits and stale log each block; `stop_hook_active` lets the stop through.

**Failed, and what was done**
- **Recovering the session's keys after compaction.** The permission checker blocked reading the ElevenLabs, Pexels and cPanel keys from the transcript, and also blocked searching Pexels and Unsplash without a key. Not worked around.
  - Lesson in `CLAUDE.md`: ask the owner for keys at the start of any session that will narrate, fetch photos or deploy.
  - The article uses a library photo instead.
- **First cover draft.** The headline wrapped "— if" to the start of line two, and the supporting line repeated the headline. Changed to a comma and her caption line.
  - Rule added: no leading dash; the post title matches the cover line.
- **Checker bug.** It passed two `-o` flags to curl, so the MP3 went to /dev/null and every audio check failed. Fixed.
- **Stop hook too strict.** On its first real use, the stale-log check counted a rebuilt `dist/` zip as unlogged work. `dist/` is now excluded. The unpushed and not-on-default blocks were verified in the same run.
- **Stale file.** One check run failed right after `git stash pop` because the dev server served the old `cms.php` for a moment. It passed on rerun. Noted in the environment section.

**Open** (narration and deploy were finished later the same day; see the entry above)
- Erika to review the added caution.

---

## 2026-10-01 — Selling article; Bold covers; CLAUDE.md and tools

**Done**
- **Selling article.** Published *Nobody should know your house better than you do* (`/before-you-list`), narrated.
  - Added a general disclosure note.
  - Left out her on-camera line suggesting buyers could skip their own inspection.
- **Cover templates.** Built five, with `tools/covers/blog.js`. The owner chose **Bold** and it became the house template, applied to all three articles.
  - Portrait: the pink-suit image, labelled AI-generated in the library.
  - Sam's Club is shown by the sign cut from her video, not a logo file.
- **Notes and tools.** Wrote `CLAUDE.md`. Committed `narrate.py`, `stt-check.py`, `deploy.sh` and `live-mirror.py`, which until then existed only in a temp folder.
- **`ceo_erika`.** Wrote the note `memory/business/erikakpage-blog.md`.

**Failed, and what was done**
- **Owner feedback.** The owner had to ask to see a deployed cover: always show the result.
- **Old photo.** The owner said not to use the old selfie: don't reuse it.
- **Cover gates.** The overflow gate failed by 4–7 px on descenders; fixed with padding under the headline. The Studio template headline ran into her hair; the checks can't see that, so look at every cover.
- **"Blog enquiry" form.** An article with no guide sent "Guide request: free guide". Fixed.
- **Short link anchor.** Short links added `#guide` to an article with no guide. Fixed.

## 2026-09-30 — Investing article; guides joined to articles; products page

**Done**
- **Digital Products.** Made the page real: per-guide pages, gated downloads, admin screen, and filter chips that actually filter.
- **Investing article.** Published *Owning real estate and investing in it are not the same thing* (`/invest`), offering the Hidden-Value Checklist.
- **One `product` field** now joins each article to its guide.
- **Sam's Club cover**, cut from a frame of her video.

**Failed, and what was done**
- **Offer box.** It was hard-coded to Closing Costs 101 in eight places, so the second article advertised the first one's guide. Now driven from the product.
- **Admin save.** Saving the Blog admin screen blanked `short` and 404'd the short link. Found by a round-trip test and fixed.
- **TTS mishearing.** It read "not a valuation" as "not evaluation". The sentence was rewritten and regenerated.
- **Banned word.** "leverage" was caught in a draft.
- **`mp3_duration()`** was given an absolute path and returned 0; it needs a path relative to the site root.
- **Host holding page.** The anti-bot page returns HTTP 200; that caused false failures until checks moved to page completeness.

## 2026-09-29 — Blog launch, first article, Closing Costs 101

**Done**
- **Blog launch.** Blog, SEO layer, and *Closing costs in Georgia, explained*, narrated in her cloned voice.
- **Closing Costs 101 PDF**, built by `tools/build-guide.mjs`, with the offer box above the fold.

**Failed, and what was done**
- **Stretched pictures.** Body pictures shipped stretched to 712×1000. The aspect-ratio check was added.
- **PDF fonts.** They fell back silently. Grepping the PDF for a font name gave a false pass; a per-character font check replaced it.

## Before 2026-09-29

Built in July and August 2026, as listed in `git log`:
- the static mockup and its client revisions;
- the PHP+MySQL CMS with its admin;
- Lofty CRM lead sync;
- photo placement, the phone layout, real video and the market pages.
