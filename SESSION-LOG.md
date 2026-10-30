# Session log

Newest first. One entry per session or piece of work: what was asked, what was done,
what passed, what failed (and how it was fixed), and what is still open. The lessons
in here are also folded into `CLAUDE.md`, which is the current truth; this file is the
history behind it. See "The loop" in `CLAUDE.md`.

---

## 2026-10-30 — The VA page as its own company (Erika's 30 Oct meeting)

**Asked.** The meeting (Google Doc): keep the VA company separate from her real estate world, on its own
domain; a professional VA's picture in front, her photo smaller with "why I founded this company"; one page
for every industry; no property tours or "only for real estate agents?"; $4–8 an hour or by the project;
common tools (HubSpot, Salesforce); a list of domains for her to pick.

**Done.** Page reworked (`lib/landing-va.php`, `assets/css/landing-va.css`, `landing-pages.php`); landing
pages can now have **their own domain** (routing in `index.php`, guard in `admin.php`, own-domain addresses
in emails, canonical, share tags, Stripe and leads); the VA page signs as its company; PDF lists updated.
Deployed (15 files, FAILURES: 0; static files byte-compared). The page was already published, so the
admin's saved copy was brought up to date with a one-off script (dry run, run, deleted: now 404). Domain
availability checked at the .com registry (RDAP).

**Passed.** Locally: desktop and phone, no sideways scroll, no console errors; own-domain routing on a test
host (page at `/`, everything else to `/`, the admin sent back, the download from the same host);
`tools/check-landing/run.sh` **ALL PASS**; the PDF build (no clipping, no fallback). Live: the new page,
title, photo, founder section, prices and options; no Axen or site links on it; 1039 and /blog still load.

**Failed, then fixed.** An undefined-key warning for pages with no layout (since 29 Oct); the lead's page
address was "/" on an own domain (now the full address); two PDF rows outside their group and an overflow.

**Open.** Erika picks the domain and the name; then cPanel → Domains and the editor's "Own domain".

---

## 2026-10-30 (later) — Separate YouTube videos stopped

**Asked.** "No need for separate youtube videos anymore, note it down please."
**Done.** CLAUDE.md now says so in every place the YouTube step was: the daily hand-over (no YouTube
part), the hand-over fields, the social-pack section, the `episode.py` notes (kept for the record)
and Open items. The channel takes every episode from the podcast's RSS feed. Videos 1–9 were made;
none after. `ceo_erika` STATUS says the same.

---

## 2026-10-30 — "Closing soon? A shutdown could slow you down."; podcast episode 15

**Asked.** "Now do today's blog and next episode please."
**First.** `main` was 6 commits ahead (another session: the VA services page, episodes 1–14 checked
live against the RSS feed, and **YouTube now takes the RSS feed**, so separate YouTube videos are on
hold until the owner says). Merged. The 29 Oct speed fixes were still live (landing page srcset and
share JPEG checked). The video repo had no new edits.
**The article** `government-shutdown-and-your-closing` (Buying, `/shutdown`).
- Her unused edits all repeat earlier articles (`lease-negotiation`'s list is in the restaurant
  article). Her older shutdown video `raw-task-1-changes` was unused, and it is timely: H.R. 6500
  (read as enrolled on govinfo) funds the government and the NFIP only to 11 Dec 2026.
- Her three points, each set against what the agencies published: flood insurance (CRS IN10835: new
  policies can't be written in a lapse; past lapses stopped closings in flood zones; lapses Oct–Nov
  2025 and Feb 2026); IRS transcripts (shown both ways: IVES stopped in 2018–19, "will remain
  available" in 2025); FHA (HUD's 2025 plan), VA (VA's 2025 plan), USDA (NAHB, Oct 2025: usda.gov
  refused curl and WebFetch). Her "FHA is the most common loan" line left out (no source).
- Two claims tightened before publishing: who must require flood insurance (now the CRS's words) and
  "law" → "bill" (signing not read at an official source). "The one most affected" (USDA) was my
  word and is gone.
- Photos B50–B52 (Capitol dome as the cover tile, a flooded house, papers and a calendar).
- **Episode:** 933 words, 327 s, -19.8 LUFS. The first take's levelling step failed without a
  message; the second take levelled cleanly. The transcript matches the script.
- Checks: ALL PASS locally and live; MP3, cover and a body picture byte-identical live.
**Podcast episode 15** (Atlanta weekend), "Recorded October 16, 2026", plus a line that Phipps
Plaza's free Friday movies were expected to run through October (the episode says to check the
schedule).
**YouTube:** not made today; the channel takes the feed. Asked the owner whether separate videos are
still wanted.
**Open.** ★ Update the shutdown article after 11 Dec 2026 or when Congress acts. Erika: review it.
NEXA / Andrew: November's two start Sunday.

---

## 2026-10-29 (night) — podcast status checked at the source; the VA named nowhere

**Asked.** The owner: the command center showed the wrong number of episodes live ("why have you not
checked with other repos?"); episode 14 went up on RSS.com today; YouTube is connected through the RSS
feed; show the edited videos too; never name the VA anywhere. Also: her company is Talaris Consulting
Group, not Polaris.

**Done.** Read the RSS feed: 14 episodes live (Season 1, 2–14 numbered; episode 1 has no number). The
command center now shows 1–14 live on RSS.com and YouTube, and lists the 60 finished edits from the video
repo. The VA's names were taken out of every file in the four repos (here: the Escalate launch plan);
"Polaris" corrected to Talaris Consulting Group in `ceo_erika`.

**Failed, then fixed.** The board's episode statuses had been carried over from the hand-over list, not
checked; its README now says to check the feed and the video repo on every sync.

**Open.** Set episode 1's number in RSS.com. Ask the owner whether separate YouTube videos are still
wanted now that the channel takes the feed.

---

## 2026-10-29 (evening) — VA services page deployed

**Asked.** The owner sent the cPanel user and key to deploy.

**Done.** Before uploading, compared the live sizes of the root PHP files, every `lib/` file and the
landing stylesheets with `main`: everything matched except the VA files themselves, so nothing older
was underneath them. Uploaded with `tools/deploy.sh`: `landing.php`, `landing-pages.php`,
`lib/landing.php`, `lib/landing-va.php`, `lib/admin-landing.php`, `assets/css/landing-va.css`, the
PDF, its cover and the two cut-out WebPs. FAILURES: 0.

**Passed.** Each static file byte-compared over HTTP; `/page/va-services` answers 404 to a visitor
(it is a draft); `/page/1039-mariners-court` still renders in full.

**Open.** The owner publishes it in the admin (no admin login here) and sends one test lead. Rotate
the cPanel key: it was pasted into chat again.

---

## 2026-10-29 (later) — VA services page and VA Wish List redesigned

**Asked.** The owner rejected the first page and PDF as dated ("do some research then design it… find some
design skills"). Then: use the pink-suit photo, Erika likes it.

**Research.** BELAY's VA landing page (outcome headline, proof beside it, services as cards, a short
sequence, the form late); studies on form length (short forms for a lead magnet; a long form converts
better split into steps, contact details last). The design follows the artifact-design guidance: a
token palette from the subject (her pink suit: espresso, dusty rose, gold), the site's Fraunces with
its italic for the one bold moment ("to-~~do~~ … *to-done*"), one orchestrated animation.

**Done**
- A **page design option** (`layout`), with `va` = `lib/landing-va.php` + `assets/css/landing-va.css`:
  hero with her cut-out in a dusty-pink arch, an animated "to-done" board and her "10-minute
  conversation" line; who it's for; a services grid; her "to-done list" quote; three steps; the
  form beside the PDF's cover; six questions; a close; a sticky phone bar. **Three-step form**: what
  you'd hand off (chips) → how much help → where to send it. Thank-you page in the same style.
- The PDF rebuilt as a **4-page worksheet**: cover with her photo; tick + hours for every task; your
  first five; how to start. `build-guide.mjs` now installs Fraunces Italic.
- Page settings: layout `va`, the pink-suit photo (also the link preview), button "Get the Free VA
  Wish List".

**Passed.** Desktop 1440 and phone 390: no sideways scroll, no console errors; an end-to-end lead
through all three steps on a phone reached the thank-you page and Leads with every answer;
`tools/check-landing/run.sh` **ALL PASS** (rounds 1–3). PDF build: no clipping, no font fallback,
every page's text present, 1.1 MB.

**Failed, then fixed.** The headline rendered dark on dark and the closing button's text vanished:
`landing.css`'s `.lp h1` and `.lp a{color:inherit}` beat bare classes (now prefixed, and noted in
CLAUDE.md). On phones the quote bubble covered her face (it now sits above the picture). The
thank-you card's text was light on white. A test click on `text=Medical office` hit the "who it's
for" line instead of the chip (test fixed; noted).

**Open.** Deploy (cPanel keys) and publish; one live test lead; Erika to OK the words. The "What does
a VA cost?" answer says it depends on the hours and is settled on the call: check that is how she
prices it.

---

## 2026-10-29 — VA services landing page and the VA Wish List

**Asked.** Erika, through her VA: promote VA services too, with a landing/splash page on her site for
lead capture. The VA then sent the team's VA services flyer for detail.

**Done**
- `landing-pages.php`: new page `va-services` (`/page/va-services`), a **draft**. Her quotes ("You need
  a VA" video; her 27 Oct meeting); questions on who they are, what they'd hand off first (13 boxes),
  hours a week and start date; Lofty source "Website – VA Services", tags VA Services; indexable.
- `tools/guides/va-wish-list.html` → `assets/guides/va-wish-list.pdf` (3 pages, built and checked by
  `build-guide.mjs`: no clipping, no font fallback, every page's text present, 71 KB). The page
  delivers it as a link, so no admin upload is needed.
- **New per-page "line under her name"** (`brand`): the eyebrow, the visitor email and the receipt read
  "Erika K. Page · VA Services" on this page instead of Axen Realty. Default unchanged.
- Dot.kode (the flyer's brand, email and website) and NSOL BPO are left out; no prices.

**Passed.** Local render on desktop and phone (no sideways scroll, no JS errors); a test lead reached the
thank-you page and the Leads table with every answer and the right Lofty source and tags;
`tools/check-landing/run.sh` **ALL PASS** after the `brand` change (local SQLite, restored afterwards).

**Open.** Deploy (cPanel keys) and publish; one test lead on the live site; Erika to OK the words and
say whether Dot.kode may be named and what the service is called.

---

## 2026-10-29 — The site's speed; "Want your deposit back?"; podcast episode 14; YouTube episode 9

**Asked.** "Post an article for today and give me the next episode… check why the website is very
slow… as we are posting more and more the website is getting slow… fix this first."
**Speed: measured, then fixed.**
- Server: PHP adds 0.1–0.15 s over a static file; the account is far under its CPU, memory and
  process limits (cPanel `ResourceUsage`). Not the cause.
- Chrome at phone speed (4× CPU, slow network): largest picture in about 2.1 s live. The HTML is
  all 29 pages at once (286 KB, 54 KB compressed), growing about 1 KB per article. Not the cause.
- **The access log was.** The October log (downloaded through cPanel) showed 5.5 GB of bandwidth,
  2.5 GB of it the Speaking page's two uploaded autoplay videos (3.6 MB + 1.9 MB), fetched 1,063
  times with `/`, `/blog` and articles as the referrer: phones (Safari, Facebook's in-app browser)
  downloading them on every page, because every page carries every other page. Fixed:
  `media_tag()` writes `data-src` and `preload="none"`; `index.php` gives a video its `src` only
  while it's on screen and pauses it when it leaves. Posters made for both uploads (ffmpeg) and
  put beside them.
- **The 1039 Mariners Ct page** showed its 1.86 MB PNG as the picture and the link preview:
  Facebook fetched it 368 times (0.75 GB). Now the WebP ladder (56 KB at 1200 wide) and a
  1200-wide JPEG preview (129 KB), made once by the new `img_share_copy()`.
- Verified live in a browser: no video requests on `/`, `/blog` or articles; on `/speaking` each
  loads when scrolled to and pauses when you leave. `check-article.mjs` gained a check that fails
  on any `<video src>` (shown failing on the old `cms.php`, passing on the new).
- Landing QA (all rounds) and `check-article --all`: pass locally after the change.
**The article** `security-deposits-in-georgia` (Buying, `/deposit`): "Want your deposit back? It
starts on move-in day."
- Her material is running out (her notes say so; her commercial edits repeat the restaurant
  article; the Smyrna tour repeats the stairs article). Her `mentee-first-deal` edit lists what an
  agent does for a tenant ("negotiate the lease… the move-in move-out inspection… not just open
  doors") and "I am a certified property manager". The mentee and the fee are left out.
- Every rule is from DCA's *Georgia Landlord-Tenant Handbook* (revised 8-29-24): two months' rent
  cap; escrow and the move-in list for landlords with more than ten units or an agent; the
  move-out list in three business days, the tenant's five; deductions vs wear and tear; 30 days;
  last known address and 90 days; three times plus fees.
- Photos B47–B49. The checker failed the empty-room photo for being both the cover tile and a body
  picture; it's the cover tile only now. The cover's sub-line is her own words ("…not just open
  doors."), shortened so the square art doesn't leave "doors." alone.
- **Episode:** 1,164 words, 383 s, -19.6 LUFS; "wave" corrected to "waive", "great example" and
  "management company" corrected before recording. The transcript matches the script.
**Hand-overs.**
- **Podcast episode 14** (trades), "Recorded October 15, 2026" and the BLS May 2025 date in the
  note. Its dated lines still hold (May 2026 wages come out next spring).
- **YouTube episode 9** (bank vs broker, NEXA). `--note` puts its text on the video frame, and the
  lender disclosure overflowed it; `episode.py` has a new `--disclosure` that goes in the
  description only. Listen-back clean.
**Open.** The owner: re-scrape the 1039 Mariners Ct link in Facebook's Sharing Debugger; consider a
720p copy of the Speaking video. Erika: the deposits article. NEXA / Andrew: November's two due.
**Missed, and fixed.** The turn ended on "the live check is still running" without the hand-over;
the owner asked for it ("always provide everything… never ever miss it"). Sent in full, and the
rule is now in CLAUDE.md *Hand-overs*: the last reply of a publishing turn is the complete hand-over,
even while a check is pending.
**Live check of every article:** 23 passed live (WARNs only: sites that refuse non-browsers). The run
hit the 30-minute background limit on the 24th (closing costs), which then passed on its own,
including the new video check. Lesson in CLAUDE.md: run the live `--all` in two halves.

---

## 2026-10-28 — "Never set foot in it?"; podcast episode 13 (with a Treasury update); YouTube episode 8

**Asked.** "Now do today's blog and next episode please."
**Her videos into the brain.** The video repo had nothing newer than this morning (58 edits, none
new). `voice-options` was mined (nothing new); `respect-your-time` is marked used.
**The article** `sight-unseen` (Buying, `/sight-unseen`): "Never set foot in it? Don't send the
money yet."
- From her "Respect your time" edit (part 1, 21 Sep): her general lines on virtual tours only ("I
  absolutely have"; "extremely, extremely rare… never set foot in"; "COVID taught us that… It can
  happen, but it's rare"; four tours and "I wanna come see it"). The mentee and her two clients
  aren't described. Her on-camera "twenty-six years" is "24+ years" in the article; the script
  says neither.
- Facts: the FTC's *Rental Scams Explained* (Aug 2024): what a rental scam is, the four steps,
  "Anyone who demands that you pay these ways is a scammer". Georgia AG's lost-money page: bank or
  wire company, IC3.gov, the FBI field office within three to four days, and where to report.
- Photos B44–B46; Bold cover with her line "It can happen, but it's rare."
- **Episode:** 793 words, 252 s, -19.9 LUFS. The full transcript matches the script.
**Failed, and what was done**
- **`check-article` failed the FTC handout with a 404**, but the link is good: every FTC host
  (ftc.gov's home page included) answers this container a 1,301-byte "Page Not Found", and WebFetch
  downloaded the same 208.8 KB PDF. The checker now treats a 404 whose host's home page also 404s
  as a WARN ("host refuses this container"). A real dead link on a working host still fails.
**Podcast episode 13** (Wall Street). Its ★ facts were re-checked first: on 22 Oct Treasury
issued **proposed** regulations under Sec. 1001 (press release sb0665: an NPRM, 30 days of comment
after Federal Register publication; the ban still begins 7 Jan 2027). The episode's "the rules
written under it may add more detail" is still true. The article got a dated update paragraph and
source 8 (`updated` 28 Oct, deployed, seen live); the research notes got the rows. The episode
description says "Recorded October 14, 2026" plus the update. Search results also called it
"Phase 1"; not in the release as read, so not used.
**YouTube episode 8** (open house, "Recorded October 9, 2026."): listen-back clean (Erica/Erika,
wanna, use/used only).
**Passed.** `check-article --all` locally (FTC WARN only); live for `sight-unseen` and the Wall
Street update; the MP3, cover and a body picture byte-identical live.
**Open.** For Erika: the sight-unseen article. Next: podcast 14 (trades), YouTube 9 (bank vs
broker, NEXA disclosure). NEXA / Andrew: October 2 of 2; November's two still due.

---

## 2026-10-27 — Her edited videos into ceo_erika; "On septic?"; podcast episode 12; YouTube episode 7

**Asked.** "Let's do a blog and next episode for today as well. Also the new videos from the edit
repo, do add those to Erika CEO brain as well so it's always updated and knows Erika's every update."
**Her videos into the brain (`ceo_erika`).**
- `Video_Edting_Erika` attached and cloned. A plain clone broke (5 GB); a blobless clone works:
  `--depth 1 --filter=blob:limit=2m`, then `git fetch --depth=1500 --filter=blob:none` for dates.
- New `ceo_erika/scripts/sync_video_edits.py <video repo>`. For every project with final captions
  it writes `intake/edits/<project>.md` (dates, deliveries, README, post copy, what is said) and
  `INDEX.md`, and prints NEW and CHANGED ones. Mined status lives in `intake/edits/mined.tsv`.
- 57 edits (5 Aug to 26 Oct) copied. The 14 October edits are mined into 6 new notes and
  `business/arc-gaming`:
  - `identity/certified-property-manager`: "I am a certified property manager";
  - `patterns/in-service-not-servants`: "we are in service… but we're not servants";
  - `voice/no-notes`, `voice/ai-needs-an-ai-operator`, `preferences/home-cooked-meals`;
  - `domain/septic-peak-design-flow`.
  The cruise, missing-iPhone and podcast-launch edits were already in memory; Wisdom from a
  Founder is her AI avatar. 43 August–September edits are copied but not yet mined.
- `ceo_erika` CLAUDE.md §1 step 5: every session runs the sync and mines what's new.
**The article** `septic-systems-explained` (Buying, `/septic`): "On septic? Ask how many people it
was built for."
- Built from her new "Septic Info" edit (26 Oct): her explainer only. The property manager on her
  call isn't quoted or named.
- Facts from the Georgia DPH *Manual for On-Site Sewage Management Systems* (June 2026, 283 pages,
  incl. Rules 511-3-1) and the EPA:
  - flow by bedrooms, on peak flow;
  - the design basis 75 gpcd × 2 per bedroom = 150 gallons per bedroom (her "150 per bedroom",
    confirmed);
  - tank 1,000 gallons for 1–4 bedrooms, +250 each over four, +50% with a grinder;
  - the bedroom definition; additions must be permitted and inspected; work on the lot can void
    the approval;
  - a rooming house at 60 gallons a bed; the county permit; county performance evaluations;
  - EPA: inspect every 3 years, pump every 3–5, the drainfield.
- dph.georgia.gov's HTML pages answered 404 and rules.sos.ga.gov 403, but the manual's PDF
  downloads. I first wrote the manual's URL as `media/92626`; it was `92616`. Corrected before
  publishing, and three section labels in the notes were corrected after checking the headings.
- Photos B41–B43 (pumping-truck stock was foreign and rejected). The cover's first sub-line left
  "is." alone on a line, so another of her lines was used.
- **Episode:** 830 words, 308 s, -19.5 LUFS; "and it's pretty clear" cut before recording. The
  transcript matches every figure.
- **Checks:** ALL PASS locally and live, plus `/septic` against the origin; the MP3 and cover are
  byte-identical.
**Hand-overs.**
- **Podcast episode 12** (Fannie Mae 620, NEXA) with the lender disclosure and "Recorded October
  13, 2026" in `--note`. Its "right now" line was re-checked: Selling Guide B3-5.1-01 is still the
  4/22/2026 version and still says "A minimum credit score is not required for DU loan casefiles."
- **YouTube episode 7** (restaurant space, "Recorded October 9, 2026."); a clean listen-back.
**Open.**
- For Erika: the septic article and episode. It quotes a video she may not have posted yet (the
  team is waiting on her about the manager's voice).
- 43 earlier edits to mine.
- The command center still can't be synced from here (no key).

---

## 2026-10-26 — "The week before closing"; podcast episode 11; YouTube episode 6; a fresh container

**Asked.** "Now do today's blog and next episode please."
**The container had been replaced.**
- The checkout was an old commit (f0e589e) with no CLAUDE.md; fast-forwarded to `origin/main`
  (d424c6a).
- `ceo_erika` was missing. It is attached as `ptoayyazsharif/ceo_erika` (it moved to `CEO_Erika`);
  cloned and registered.
- **Its `main` lacked the blog records pushed on 21–23 Oct**: they had gone to a side branch
  (`claude/erika-ai-memory-system-73dbz0`). Merged into `ceo_erika` `main`. The CHANGELOG conflict
  kept both sides; PERSONA.md and system-prompt.md were rebuilt.
- Rebuilt locally:
  - `config.local.php` (SQLite), then `/setup.php` (admin `tester`);
  - the brand fonts (`node tools/build-guide.mjs --fonts-only`, new);
  - `/tmp/pdfvenv` (fonttools, pypdf).
- **The first cover rendered in a fallback serif**: Fraunces and Archivo weren't installed. Fixed:
  - `blog.js` now refuses to render until `fc-match` finds both;
  - `build-guide.mjs --fonts-only` installs them without rebuilding a guide.
- Nine tools named a Chromium build that no longer exists (`chromium-1194`). They now use
  `/opt/pw-browsers/chromium`. The landing QA passes in this container (143 checks).
**Done.**
- **Topic choice.** The unused recordings left are mostly mindset pieces. `who-pays-for-closing-costs`
  is already used in the closing-costs article ("different and negotiable every single time").
  Used instead: her closing-day reel `Dce4h7rJ52a`, a first-time buyer on her final walkthrough
  with Erika ("My hand was held. I was educated."; caption: "the experience is what she'll
  remember").
- **Article** `the-week-before-closing` (Buying, `/closing-week`):
  - the Closing Disclosure: five pages, received at least three business days before closing
    (12 CFR 1026.19(f)(1)(ii)); only APR, loan product or a prepayment penalty restart the wait
    (f)(2)(ii); other changes get a corrected disclosure at or before closing (f)(2)(i);
  - the final walkthrough and seller credits (CFPB *Close the deal*);
  - wire fraud: two trusted people, call-back, wire recall, ic3.gov (the CFPB's archived scams
    post; its 2015–2017 figures left out);
  - what to bring and your rights at the table, and after closing (CFPB checklist, 2019).
  - The client is not named (she names herself on camera; the caption names her).
  - Research table: `tools/research/the-week-before-closing.md`.
- Photos B38 (walkthrough, RDNE), B39 (signing, Kampus; a different photo from A1), B40 (phone,
  Vitaly Gariev; the cover tile). One signing shot was rejected: its legible text was a business
  "capex" form.
- **Episode:** 860 words, 315 s, -19.8 LUFS. "Here's something people don't always know" was cut
  before recording; the full transcript matches.
- **Checks:** ALL PASS locally and live, plus `/closing-week` against the origin; the MP3 and cover
  are byte-identical.
- **Hand-overs:**
  - **Podcast episode 11** (stairs).
  - **YouTube episode 6** (relocating, `--note "Recorded October 9, 2026."`). Its listen-back
    caught a caption reading "14,000, 801." for "fourteen thousand, eight hundred and one".
    `digits()` now keeps a number going past a comma after thousand/million. No handed-over video
    had the pattern; stairs, trades, ground lease and 50-year do, and now render right.
**Open.**
- For Erika: the new article and episode.
- The `ceo_erika` STATUS.md website section is updated. The VA's command center
  (https://erika-command-center-pi.vercel.app) wasn't changed from here; see its README.

---

## 2026-10-23 (later) — "What the real estate class won't teach you"; podcast episode 10; YouTube episode 5

**Asked.** "Now do today's blog and next episode please."
**Done.**
- **Picking the material.**
  - `follow-the-gps` and `versus-competition`, the unused transcripts named in her notes, are
    mindset pieces, not real estate.
  - Earnest money and title insurance (reel `Da-nHRdCAh8`, title part 2) are already in the
    closing-costs article.
  - **Her bird-dog reel `DbyPKNCCNFJ` was rejected.** She says she compensates her bird dogs,
    and GREC's FAQ says a licensee may not pay a fee or give a gift to an unlicensed person for
    a referral (License Law 43-40-25(a)(17)). Flagged to the owner; the reel is already public on
    her Instagram.
  - Used instead: her three "what do you wish you knew in your first year of real estate?"
    reels (`DcrGxmuCqHx`, `DcoGkAvih2-`, `DcqrYpwCsfR`) and her mentor video
    (`erika-page-axen-mentor-remake`).
- **Article** `what-the-real-estate-class-wont-teach-you` (Lifestyle, `/new-agent`).
  - Georgia's salesperson-licence steps from GREC (licence page, FAQ, education rules) and PSI's
    candidate bulletin (7/1/2026): 18+, high school, 75-hour course, GCIC report, PSI exam, a
    sponsoring broker, apply within three months, no work until the broker has the wall
    certificate and pocket card.
  - What the exam outline covers (contracts 19%; Do-Not-Call and social media, but no section on
    finding clients).
  - The first year: 25-hour postlicense course or the licence lapses; 36 hours CE with 3 of
    license law; one broker; broker-approved advertising; no referral gifts to unlicensed people;
    negotiate the contractor agreement.
  - Then her lessons (prospecting, follow-up and consistency, own leads, a mentor) and her mentor
    video's lines. Call to action: reach out to her (`/contact`).
  - Her "12–17 touches / quits after 2" figure is left out (no source; her compliance note says
    cut, don't caption). AI Erika and the fees are left out too.
  - Research table: `tools/research/what-the-real-estate-class-wont-teach-you.md`.
- Photos B35 (studying, Karola G), B36 (mentor and agent, Edmond Dantès), B37 (agent on the phone,
  RDNE; the cover tile); cover, podcast art, YouTube thumbnail.
- **Episode** (`tools/podcast/what-the-real-estate-class-wont-teach-you.txt`): 929 words, 356 s,
  -19.6 LUFS. Audited for flourishes before recording ("A whole year.", "the part that decides
  whether you make it", "this one surprises people" taken out). The full transcript matches: every
  figure right.
- **Checks:** `check-article.mjs`: ALL PASS locally and live, plus the `/new-agent` 301 against the
  origin. The MP3 and cover are byte-identical over HTTP.
- **Hand-overs:**
  - **Podcast episode 10** (rollback rate). Its dated lines are all 2026 and still true.
  - **YouTube episode 5** (homestead), with `--note "Recorded October 5, 2026."`. It is the first
    YouTube video from a podcast script rather than a read-aloud article. `episode.py` aligns the
    captions with the script, and there are no chapters (as designed). The listen-back shows only
    the transcriber's "Erica" and one extra "of" at 3:34.
**Open.**
- For Erika: the new article and episode.
- For the owner: the bird-dog reel.

---

## 2026-10-23 — A real receipt for buyers; paid checkouts settle without the webhook

**Asked.** The owner made a test purchase with a test key (no webhook yet). The buyer got the
plain free-download email ("Your download: Test Digital Product", from "ErikaKPage Website").
"Is it okay for the customer to receive this plain email after they purchased something? No
receipt for payment details, right? Please add more thought into it."
**Done.**
- **The buyer's email is now a receipt** (`landing_email_receipt()`, `lib/landing.php`).
  - Subject "Your receipt and download: <item>", with "[TEST] " in front in test mode, and a
    "Test purchase, no real money was charged" note at the top.
  - It comes from **"Erika K. Page"**, not "ErikaKPage Website"; the free email does too
    (`send_mail()` takes a sender name). Replies go to the owner.
  - It has the download button, the 30-day note and "reply and we'll send a fresh one".
  - A receipt box: order number **EKP-00042** (the lead's id), date (Atlanta time), item,
    billing, amount paid in USD, the card ("Visa •••• 4242"), how it reads on the card
    statement, and a link to **Stripe's own receipt** (or, for a subscription, the invoice
    number and its hosted invoice).
  - For a subscription: "renews automatically every month at $X until you cancel. To cancel,
    reply to this email."
  - The details come from Stripe after payment (`stripe_receipt_details()`: the payment
    intent's charge, or the customer's charge and the invoice for a subscription). Only fields
    every API version has. Best effort: if Stripe is slow, the receipt goes without the card.
- **The owner's sale email:** "[TEST] New sale: <item> ($99.99) — <name>", with the order
  number and "Paid $99.99 · Visa •••• 4242".
- **The thank-you page** shows "Payment received: $99.99 · Order EKP-… (· test mode)" and "Your
  receipt and this link are in your email."
- **Without the webhook a paid buyer could get nothing.** If they closed the tab before
  Stripe sent them back, the lead stayed "Payment pending" and no email went. Now opening
  **Leads** asks Stripe about pending checkouts (`stripe_reconcile()`: last 3 days, each at most
  every 10 minutes, 10 per visit). A paid one gets its receipt and download; an expired one goes
  to Lofty as abandoned. The webhook still does this at once and is still the setup.
- **Leads, a sale:** the order number, card, test-mode flag and "The buyer's receipt ↗". New
  button **"Email the receipt again"** (or "Email the link again" for a free lead) sends a fresh
  30-day link.
- **QA** (`tools/check-landing/`): the fake Stripe answers the new calls and can let a
  checkout expire; the SMTP catcher keeps the From header and the HTML. Round 3 checks the
  receipt (both kinds), the owner's email, the thank-you page, settling from Leads (paid and
  expired, and nothing twice) and the resend. **143 checks, all pass** (SQLite). Round 1's
  "needs its file" check now looks at the Mariners row, since Alcovy is a second draft.
- Deployed: `cms.php`, `landing.php`, `lib/{landing,admin-landing,stripe}.php`,
  `assets/css/landing.css`. Live: Mariners 200, Alcovy still 404, the admin and an article
  complete, the CSS byte-identical.
**Open (owner / Erika).**
- Add the webhook (steps in the reply); until then, open Leads after a sale.
- In Stripe: business name, support email and statement descriptor (Settings → Public
  details); turn on Stripe's own "Successful payments" and "Refunds" emails if wanted (live mode
  only).
- A refund policy: none is in her files. Decide one before selling.
- Before selling a subscription: the Stripe customer portal for self-serve cancelling.

---

## 2026-10-22 (night) — 1896 Alcovy Bluff Way investor-package page, as a draft

**Asked.** "Make a page for this one address as well like … the 1039 Mariners Court Investor
Package … but don't publish it yet: 1896 Alcovy Bluff Way, Lawrenceville, GA 30045."
**Done.**
- `landing-pages.php`: new page `1896-alcovy-bluff-way` (`/page/1896-alcovy-bluff-way`),
  **unpublished**. "Get the 1896 Alcovy Bluff Way Investor Package", the Mariners page's
  button, six questions, consent line, thank-you text and Lofty settings (Investor lead
  type; source "Website – 1896 Alcovy Bluff Way"). The file it expects is
  "1896 Alcovy Bluff Way Investor Package.pdf".
- Only the address came with the request, and the address appears nowhere in `ceo_erika` or
  on her Stan store. So the description is the address and "Investment Opportunity", with no
  beds, baths or size, and there's no photo yet.
- Checked locally: 404 for visitors; a logged-in admin sees it with the Draft banner ("no file
  to hand over yet"); it's in the admin list; desktop and phone screenshots look right.
  Deployed: live, it answers 404 to visitors, and the Mariners page still answers 200.
**Open.** The owner adds the photo, the PDF and the property details (beds, baths, size), then
publishes. The access agreement says "offered AS-IS" and "contact the occupants", copied from
Mariners: confirm both are true of this house.

---

## 2026-10-22 (later) — 50-year mortgage article; podcast episode 9; YouTube episode 4

**Asked.** "Now do today's blog and next episode please."
**Done.**
- **Article** `fifty-year-mortgage-explained` (Buying, `/50-year`) from her November 2025 video,
  re-checked first. The idea stalled: in January 2026 Pulte said "I think we have other priorities"
  (Politico via NASCUS). The rules still cap terms at 30 years: 12 CFR 1026.43(e)(2)(ii) and
  Fannie Mae B2-1.5-02. NPR covers the November announcement and a Realtor.com example. Our own
  illustration ($300,000 at 7%, same rate): $191 a month less, about $364,500 more interest,
  $9,500 vs $42,600 paid down in ten years. Research table in
  `tools/research/fifty-year-mortgage-explained.md`.
- Photos B33 (couple with a calculator, Mikhail Nilov) and B34 (calculator on purchase papers,
  RDNE); cover, podcast art, YouTube thumbnail.
- **Episode**: 777 words, 301 s, -19.6 LUFS. The first take said "Poult" for Pulte, so the
  script spells it "Pultee" and it was re-recorded; all 15 figures and dates checked in the
  transcript.
- **Hand-overs**:
  - podcast episode 9 (bank vs broker), with the NEXA / Andrew lender disclosure added through
    `handover.py --note`;
  - YouTube episode 4 (cash is king).
**Fixed on the way.**
- `check-article.mjs`: the oldest `/blog` card (closing costs) failed "pictures loaded" for every
  article. The page scrolls inside its own container, and today's 20th article pushed that card
  below anything the window scroll reached. `loadLazy` now scrolls each picture into view, turns
  stragglers eager and waits for them all. `--all`: ALL PASS locally; the new article passes
  live too.
- YouTube captions said "24+ plus years" where the voice reads "twenty-four plus":
  `digits()` now folds "plus" into the figure.
- Deploy: 15 files, 0 failures.

---

## 2026-10-22 — Abandoned checkouts go to Lofty

**Asked.** "Add abandoned checkouts to Lofty too please."
**Done.**
- A checkout now closes after an hour (`expires_at`; Stripe's default is 24 hours), so the
  follow-up is fresh. When Stripe reports it expired (or an async payment failed), the lead
  moves from pending exactly once (`stripe_flip()`) and goes to Lofty with the page's settings
  plus the tag "Abandoned checkout" / "Payment failed" and what they were buying
  (`stripe_lofty_unpaid()`). No emails. Retrying from Leads keeps the tag.
- The Payments / Stripe screen explains it.
**QA.** Round 3 grew to 64 checks:
- nothing goes to Lofty while the checkout is open;
- expiry sends one lead, with the page's tags plus "Abandoned checkout", the answers and "Left
  without paying: $99.99"; Stripe's retry sends nothing; the buyer gets no email;
- a failed payment is tagged "Payment failed"; a buyer who paid is never sent as abandoned;
- Leads shows "Left checkout" and "In Lofty"; the checkout request carries a one-hour `expires_at`.
All three rounds ALL PASS. The one-time state change also passes on MariaDB 10.11. Deployed 3 files,
0 failures; live webhook, admin and the Mariners page checked.

---

## 2026-10-21 (evening) — Stripe paywall for landing pages (one-time and subscriptions)

**Asked.** "Let's add a Stripe paywall, a bit simple, no over-engineering, like Stan Store; Erika
can just add API keys and webhook… add subscriptions as well for later use (or hold them if the
change is massive)." Screenshot: her Stan product "The Modern Agent Success Blueprint", $99.99.
**Done.** Subscriptions were a small addition with Stripe Checkout, so they're in.
- `lib/stripe.php` (no library): Checkout session, session lookup, signature check, the
  exactly-once completion, webhook events, the Leads chips. `stripe-webhook.php` at the root.
- Admin → **Payments / Stripe**: secret key + webhook secret, endpoint to copy, events to tick,
  Save & test, last webhook received.
- Landing-page editor: step 3 **Set price** (blank = free; one-time / monthly / yearly), the total
  in the phone preview; a paid page publishes only once Stripe is set up.
- Public page: "Total $99.99" (or "$29 / month" and how billing works) above the button, a
  secure-payment note, "Payment cancelled. Nothing was charged." on return.
- `landing_capture()` split: a paid lead is kept as "payment pending"; `landing_notify()` sends the
  emails and Lofty once paid (free pages unchanged).
- Leads: Paid / Subscribed / Payment pending / Left checkout / Payment failed; links to the payment
  or subscription in Stripe; CSV Payment column.
**QA.**
- `tools/check-landing/run.sh` now runs a fake Stripe (`stripe_mock.py`) and round 3
  (`e2e3.mjs`, 54 checks): ALL PASS. Rounds 1–2 still ALL PASS after the refactor.
- The payment lifecycle was run against MariaDB 10.11 (pending → paid once → found by
  subscription → canceled; a session for another page refused): all pass.
- `check-article.mjs` on two articles: all pass. Deployed 8 files, 0 failures.
- Live: the webhook answers 405 to GET and 400 to unsigned or badly signed posts; the home page,
  an article, the admin and the Mariners page load complete.
**Caught on the way.**
- The first round-3 run failed where Stripe sends the buyer back: the return address uses the
  site address (erikakpage.com). That's right in production; run.sh now points it at the local
  server for the test.
- The local test login's password was unknown, so it was reset in the local database only.
**Noticed.** The owner has published the 1039 Mariners Ct page (it's live).
**Open.** Erika's Stripe keys; one test purchase in test mode; whether abandoned checkouts should
go to Lofty.

---

## 2026-10-21 — Ground-lease article; podcast episode 8; YouTube episode 3

**Asked.** "Let's do today's blog and next episode."
**Done.**
- **Article** `ground-leases-explained` (Investing, `/ground-lease`), researched around her reel
  `DcbXCobtMCq` and its caption. Sources: MARTA's 29 May 2012 board minutes (One Atlantic Center
  on MARTA land, 49-year lease from 1983 with extensions to 99; Lindbergh 99-year leases;
  "a preference for ground leases rather than sales"), the Atlanta Land Trust FAQs (trust owns
  the land, owner owns the house, resale formula, 25% of appreciation, owner-occupied, approved
  lenders), Fannie Mae Selling Guide B2-3-03 (lease ≥ 5 years past the loan, plus the lease
  requirements used as the "what to check" list), 24 CFR 203.37 (FHA: 99 years renewable or 10
  years past maturity). Claims table in `tools/research/ground-leases-explained.md`.
- Photos B31 (downtown Atlanta towers, K / Pexels) and B32 (empty lot, David Brown / Pexels);
  Bold cover, podcast art, YouTube thumbnail.
- **Episode** from `tools/podcast/ground-leases-explained.txt` (1,055 words, 386 s, -19.6 LUFS).
  Full listen-back by transcript matched the script.
- **Checks:** `check-article.mjs` all pass locally and live. Deploy: 15 files, 0 failures.
- **Hand-overs:** podcast episode 8 (the open house) via `handover.py`; YouTube episode 3 (know
  your house), with `--numbered-label Reason`.
**Caught on the way.**
- One script line ("it's everywhere once you know to look for it") was a claim with no source;
  cut before recording.
- The stock skyline is downtown, not Midtown (its Pexels description), so it's named that way.
- YouTube 3 first ran without `--numbered-label`; the difference list showed "Reason one" said
  where the captions had "1.", so it was re-run with the label (the CLAUDE.md rule).
**Open.** Her reel promises a free ground-lease checklist ("Comment GROUND"); none is in her
files. Ask whether she has one.

---

## 2026-10-20 (night, later) — Channel banner remade from a cut-out, with QA

**Asked.** "The banner is still not good, the bg has edges and mushy; please do a QA after
making, make them all again." The owner also sent a cut-out of her (no background).
**Done.**
- `tools/youtube/cutout.py`: a full-size (1022×1536) cut-out of the pink-suit photo. The
  owner's 407×612 cut-out is the outline; the edge is re-measured at full size against the
  photo's pink backdrop, snapped to real edges with a guided filter where the pink blazer
  meets the pink backdrop, and the backdrop is taken out of edge pixels. The faint edge
  pixels were choked after a light rim showed along her hair at 2×.
- `channel.js` rebuilt: banner, 800×800 picture, pink alternative, watermark, all on one
  drawn radial backdrop with fine grain (no photo edges, no banding), plus QA sheets of the
  phone and computer crops and her edges at 2×. All three were looked at before sending.
- `channel-setup.md` now lives in `tools/youtube/` and is copied out on every run; a clean-up
  of the output folder had deleted it.
**Tried and dropped.** rembg (isnet-general-use): clean hair but it erased the blazer (pink
on pink).

---

## 2026-10-20 (night) — YouTube channel set-up for the podcast

**Asked.** "Give things here to fill for the YouTube setup, all things, the images and all, for
the podcast" (a screenshot of Studio → Customization → Profile). Then: "this is her preferred
image" (the RSS.com show art) and "here is real image as well, no text in it" (the pink-suit photo).
**Done.**
- `tools/youtube/channel.js`: banner 2048×1152, profile picture 585×585 from the show art,
  a 700×700 pink alternative from the plain photo, watermark 150×150, the show art as the
  podcast playlist square, and a safe-area check. Every field for the Profile screen in
  `exports/youtube/channel/channel-setup.md`, all pasted into the reply as well.
- The show art saved as `tools/podcast/art/show.jpg` (fetched from the RSS feed, since images
  pasted into chat don't reach the disk). Playfair Display (her brand title face, OFL) in
  `tools/youtube/fonts/`.
**Fixed on the way.** The first banner used her red-blazer headshot and the merlot Bold style;
redone around her preferred show art. Its title sits beside her hair: a plain crop showed
ghost letters and the dots of "Explains…", and the photo's lit backdrop showed as a lighter
box. Fixed with a blurred mask along the title's edge and a background gradient sampled from
the art. A gold dot in the first profile crop: the crop now starts right of the title.
**Open.**
- The RSS.com show description says episodes are "read start to finish" and "The words are
  hers", which is no longer true. Suggested replacement sent to the owner.
- Erika's contact email going public, to confirm.
- `a7-pink-suit-ai.jpg`: AI-generated or real?

---

## 2026-10-19 (late night) — Her AI's strategy review: two proposed pages

**Asked.** The VA shared the strategy review Erika's AI wrote after the meeting.
**Done.** Open items gain the proposed podcast landing page and AI Twin inquiry page (not built;
waiting on Erika's approval of the pilot). The review itself is in `ceo_erika`
(`intake/strategy/2026-10-19-escaluxe-strategic-review.md`, `STATUS.md` tasks 6–8).

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

**Correction (same night).** The QR code she asked about was a one-off for the podcast-launch
video, already fixed by the VA in CapCut. No QR-code rule here.

---

## 2026-10-20 (late) — Hand-overs carry every field, every time

**Asked.** "No keywords? For the episode, make sure to remember every time every single thing
as an output. Add this to memory and the repo."

**Done**
- **Episode 7's keywords and YouTube Episode 2's tags were resent in full.** Episode 7's
  keywords had been given at the end of a long message; the YouTube tags were only in the zip.
- **`tools/podcast/handover.py <slug> --episode <n>`** builds the whole RSS.com block from the
  post itself: the files (with the level check), title, description (with sources for a
  researched episode), keywords (6–8, required), the settings table, and the dated lines to
  re-check. A copy is saved to `exports/podcast/`. It exits non-zero if anything is missing.
- **CLAUDE.md** has a new *Hand-overs* section near the top: every field goes in the reply
  itself, for the podcast, YouTube, social packs and articles. Publishing step 8 points to the
  script.
- **The SessionStart hook** prints the rule at the start of every session.

---

## 2026-10-20 (evening) — Listing-photos article; podcast episode 7; YouTube episode 2

**Asked.** "Do what we do daily: post a new blog and give me the next episode."

**Done**
- **Article** `/blog/listing-photos-and-drones` (`/listing-photos`, Selling): "Buyers scroll.
  Good photos stop the scroll."
  - Built around her unused reel `DcKdzUiulyk` with her photographer, Jonathan Martinez,
    flying a drone over a listing of a little over an acre. Her caption and lines are quoted.
  - Researched:
    - NAR's 2025 Profile of Home Buyers and Sellers, via Virginia REALTORS' summary: the
      search began online most often, the median search took 10 weeks, and 85% ranked
      agents most useful;
    - NAR Code of Ethics Article 12 and Standard of Practice 12-10 ("a true picture",
      images, no misleading manipulation);
    - the FAA's Part 107 pages (Remote Pilot Certificate, registration, Remote ID).
  - Two new photos: B29, a drone in front of a house (body); B30, an aerial of suburban homes
    (tile). Bold cover with her line "Meet the man behind every photo you've ever seen on my
    listings."; square art.
  - Episode: 613 words, 236 s, levelled (-19.6 LUFS).
- **Podcast episode 7** (leasing a restaurant space) handed over: levelled (-19.7 LUFS), no
  dated lines.
- **YouTube episode 2** (owning vs investing) made and handed over: 195 captions, 10 chapters.

**Failed, and what was done**
- **Lines in her voice she never said:** "Here's what I tell sellers", a buyer driving
  across town, "the right answer tells you a lot", "honestly". Removed before recording.
- **No single readable figure for "buyers who find photos very useful".** Secondary sources
  disagree (81, 83 or 87%) and the report is members-only, so it's left out.
- **Her reel's "over 25,000 buyers in our database" and "your home is probably already
  sold"** are left out: no source.
- **YouTube captions showed "1." where the voice says "Question one"** (the episode was
  narrated with `--numbered-label Question`). `episode.py` now takes the same option.
- **The YouTube description lacked the podcast links.** Apple Podcasts then Spotify are now
  in every description.

**Passed**
- `check-article.mjs`: ALL PASS locally and live. Uploads byte-identical.
- The full transcript matches the script.

**Open**
- Erika to read the article and episode.
- Next: podcast episode 8 (open house), YouTube episode 3 (know your house).

---

## 2026-10-20 — Landing pages (/page/<address>) and one Leads list for every form

**Asked.** A landing page on the site like Erika's Stan Store lead magnet for 1039 Mariners
Court (screenshots and the whole page pasted), at `website.com/page/<custom address>`, in the
site's design. A backend as easy as Stan's. Visitors answer the questions, then get the file.
An admin page listing every lead and the page it came from. All leads go to email and to
Lofty. "Do QA before and all."

**Done**
- **Landing pages:** `lib/landing.php`, `landing.php`, `assets/css/landing.css`,
  `landing-pages.php`.
  - The public page: the site's palette and type, a focused header and footer, the picture
    and pitch staying in view beside the form.
  - Server-side validation that keeps the answers.
  - A thank-you page and a signed 30-day download link (HMAC, tied to the page). The file
    is private in `uploads/private/`.
  - Noindex by default, plus a sitemap entry when the page is set to be listed.
- **Admin:** `lib/admin-landing.php`, hooked into `admin.php`.
  - A *Landing pages* list.
  - A Stan-style editor (image, description with rich text, question builder,
    file-or-link, options) with a live phone preview.
  - Draft / Publish / Unpublish / Duplicate / Delete.
  - A *Leads* screen: stats, filters, search, detail, CSV, "Send to Lofty again", delete.
- **Every site form is now a lead too.** `submit.php` records each one in the new `leads`
  table, after its email and Lofty push. Until today a missed email was a lost lead.
- **The Mariners page:** title, description, bottom title, button and all six questions
  copied word for word, deployed as a draft. It needs the owner to upload the photo and the
  PDF (neither came with the request) and publish.
- **Supporting changes:**
  - `send_mail()` takes an optional recipient, for the visitor's link.
  - `router.php` refuses `/uploads/private` locally.
  - An `mb_substr` fallback, because the live host's mbstring isn't confirmed.

**QA (all pass)**
- `tools/check-landing/run.sh`: 59 browser checks, run with a local SMTP catcher and a fake
  Lofty, on SQLite and on MariaDB 10.11.
  - Emails and the Lofty payload were read by hand: name split, phone, Investor lead type,
    tags, and every answer in the note.
  - Also tested: site-form capture, the honeypot, the rate limit (stops at 12 an hour),
    Lofty off, and the mail server down. The lead is kept and the visitor still gets the
    file.
- `check-article.mjs --all`: ALL PASS locally and live after the deploy.
- Live:
  - the draft page 404s for the public;
  - `/uploads/private` answers 403, and `landing.php` can't be opened directly;
  - home, admin login, sitemap (47 URLs) and the contact forms are fine.

**Failed, and what was done**
- **The editor lost a half-finished question after a refused save.** The server cleaned the
  posted questions before redrawing. It now redraws exactly what was typed.
- **Layout fixes from the screenshots:**
  - the description lines sat too far apart;
  - the checkbox legend was indented;
  - the form card was sticky though taller than the screen (the intro is sticky instead);
  - on a phone the download card came after the description (it now comes first);
  - the agreement text sat in a one-line box (now a textarea).
- **`deploy.sh` reported the private `.htaccess` as ABSENT.** The cPanel listing hides
  dot-files; it now asks for them.
- **cPanel's `get_file_content` rewrites the `<head>`** of HTML-looking PHP, so read-back
  compares differ falsely. Sizes match; static files were compared over HTTP.
- **MariaDB's first install failed** on a stale package index; `apt-get update` fixed it.

**Open**
- Owner: upload the Mariners photo and PDF, publish, and send one test lead.
- Erika: read the consent line and the thank-you text, which are ours.
- Whether Lofty is switched on live isn't visible from here. The Leads screen shows each
  lead's Lofty status.

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
