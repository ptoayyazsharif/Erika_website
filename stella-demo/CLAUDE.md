# Manifest — working notes

## Project
Personalized manifestation web demo for Erika Page.
PHP 8.1+ backend (plain, no framework — deploys to GoDaddy cPanel).
Vanilla JS + GSAP frontend, no build step. Web Audio API for audio mixing.

## Flow
6 screens: landing → 15-step quiz → "reading" loading theatre →
story reveal (text fades line-by-line + voice) → player (loop + ambience) →
paywall mock. Build ONE screen fully before starting the next.

## Backend
Two endpoints: `generate-story.php` (LLM → text) and `synthesize.php`
(ElevenLabs → cached mp3), plus `status.php` (config doctor). Keys live in
`api/config.php`. TTS is cached by `sha256(model|voice|speed|text)` so replays
are instant and free.

## Audio
Two `<audio>` elements (voice + ambience) routed through Web Audio GainNodes.
Ambience 0.25 gain looped, voice 1.0. When an ambience mp3 is missing the bed
is synthesised from filtered noise; when there's no ElevenLabs key the browser
speaks the story and the reveal paces off a virtual clock.

## Design
Midnight-navy → plum gradient bg, warm gold (#C9A961) accents, cream (#F5F0E8)
serif text. Fraunces for story + headings (same face as erikakpage.com), Inter
for UI. Everything fades via GSAP, nothing snaps. The story reveal is sacred —
dark screen, no chrome, serif lines appearing one at a time.
Luxury wellness product, NOT a SaaS dashboard.

## Rules
- Vertical slices, not layers (one full screen at a time)
- Real copy, no lorem
- Split text + audio into two API calls (better UX + avoids cPanel timeouts)
- One concern per file
- Every external dependency needs a fallback — the demo must never dead-end
- `fallback_story()` in `api/lib.php` and `localStory()` in `js/story.js` are
  the same story in two languages; edit them together

## Don't
- Don't cut the contrast beat ("Three years ago, I would have…"). It's why
  question 15 exists and it's the emotional centre of the reading.
- Don't ask for children's names or ages. Question 5's free-text framing gets
  the family texture without the data-sensitivity problem.
- Don't add chrome to the reveal screen.
