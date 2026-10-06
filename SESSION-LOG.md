# Session log

Newest first. One entry per session or piece of work: what was asked, what was done,
what passed, what failed (and how it was fixed), and what is still open. The lessons
in here are also folded into `CLAUDE.md`, which is the current truth; this file is the
history behind it. See "The loop" in `CLAUDE.md`.

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
