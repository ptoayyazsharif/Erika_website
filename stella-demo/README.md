# Manifest — personalized manifestation demo

A working demo product for Erika: fifteen quiet questions, a short "reading your
intentions" moment, then her own life — her names, her Costa Rica villa, her
numbers — narrated back to her in present tense over ambient sound.

The personal reveal is the entire sell. Everything else is framing.

## Run it

**On a server (what customers see):**

```bash
php -S localhost:8000 -t stella-demo
# open http://localhost:8000/
```

**On GoDaddy cPanel:** see [DEPLOY.md](DEPLOY.md). Upload, extract, paste keys,
`chmod 775 audio/generated/`. No build step, no Composer, no queue workers.

It runs with **zero API keys**. With none configured you get a template-written
story and the browser's own voice; fill in `api/config.php` and the same flow
switches to a live LLM and a real narration without touching anything else.

**One free Google AI Studio key covers both** — Gemini writes the story *and*
speaks it. Anthropic, OpenAI and ElevenLabs are all wired too; whichever keys
you fill in get used, in that order of preference.

## The flow

| # | Screen | What it does |
|---|--------|--------------|
| 1 | Landing | One line, one button. Plus "Play Erika's reading" — the pre-filled demo run. |
| 2 | Quiz | 15 animated steps, one question per screen, autosaved as you go. |
| 3 | Reading | ~6s of loading theatre. Covers API latency, builds anticipation. Non-negotiable. |
| 4 | Reveal | Dark screen, cream serif lines fading in one at a time, paced to the voice. |
| 5 | Player | Play/pause, scrub, loop, ambience (café / rain / waves) + volume. |
| 6 | Paywall | Static mock — "this is the screen your customers would hit." |

## Stack

Plain PHP 8.1+ (cURL, file-based caching, no MySQL) · vanilla JS + GSAP from CDN ·
Web Audio API for mixing · Google Fonts (Fraunces + Inter). No build step, no
framework — the folder uploads straight into `public_html` and works.

## Providers

| Job | Options, in order of preference | Configured in |
|---|---|---|
| Story | Anthropic → OpenAI → **Gemini** → built-in template | `LLM_PROVIDER`, keys |
| Voice | ElevenLabs → **Gemini** → browser speech | `TTS_PROVIDER`, keys |

Measured on the free Gemini tier (`gemini-3.5-flash-lite` + `gemini-2.5-flash-preview-tts`):

- **Story:** ~3 seconds, 420–430 words. Comfortably inside the 6-second reading theatre.
- **Voice:** ~35 seconds for a whole reading, ~2.4 minutes of audio out. Complete readings only — a partial narration is never cached.
- **Free quota:** 10 TTS requests *per day, per model*. A whole reading is deliberately sent as **one** request, and three TTS models are tried in turn, so that's roughly 30 free readings a day. Cached replays cost nothing.

Because 35 seconds is too long to hold the reveal, **pre-warm before a demo**:

```bash
php api/prewarm.php          # or open /stella-demo/api/prewarm.php in a browser
```

That writes the story and the audio into the cache and leaves a
`demo-reading.json` behind. "Play Erika's reading" then finds it and starts in
about 8 seconds, in the real voice. The debug panel has the same button.

For unlimited, instant, higher-quality narration, add an ElevenLabs key — it
answers in a few seconds and takes over automatically.

## Files

```
stella-demo/
├── index.html              all six screens, one document
├── css/style.css           design system: midnight→plum, gold, cream serif
├── js/
│   ├── app.js              flow controller, routing, reveal sync
│   ├── quiz.js             the fifteen questions + Erika's demo profile
│   ├── story.js            API calls + offline template story
│   ├── player.js           Web Audio mixing, procedural ambience, voice fallback
│   └── anim.js             GSAP transitions + starfield
├── api/
│   ├── config.php          ← ALL API KEYS HERE
│   ├── config.local.php    untracked key overrides (loaded first, wins)
│   ├── lib.php             cURL, prompt, cache, rate limit, fallback story
│   ├── generate-story.php  profile → story text
│   ├── synthesize.php      text → cached mp3/wav
│   ├── prewarm.php         generate + narrate ahead of time, CLI or browser
│   └── status.php          pre-demo doctor: what's configured, what's writable
├── audio/ambience/         cafe.mp3, rain.mp3, waves.mp3 (optional — synthesised if absent)
└── audio/generated/        TTS cache, chmod 775
```

### Where the keys go

`api/config.php` is the file to edit, and it is fine to put keys straight in it
for a demo. If this folder lives in a git repository, put them in
`api/config.local.php` instead — it is loaded first, anything it defines wins,
and it is gitignored so a key never reaches GitHub. Copy that one file to the
server by hand.

## Two endpoints, split on purpose

`POST api/generate-story.php` → `{ story, source, model, words }`
`POST api/synthesize.php` → `{ url, cached, mode }`

Text returns in a few seconds and the reveal starts on it; audio generates in a
second call. Better UX *and* neither request runs long enough for shared hosting
to time it out. Audio is cached by `sha256(model + voice + speed + text)`, so a
replay — or a second run of the same demo — is instant and free.

## Demo aids

- **"Play Erika's reading"** on the landing screen — her profile pre-filled, straight into the reading. Never let a live demo start with cold typing.
- **`?debug=1`** — a panel with voice swap, regenerate, cache clear, the raw profile JSON, and the exact prompt that was sent.
- **`?p=…`** — a share link that reloads with a given set of answers ("Copy share link" on the player writes one).
- **`?demo=1`** — pre-fills the quiz with Erika's answers without skipping ahead.
- **`api/status.php`** — open it before the demo: it tells you the LLM provider, whether TTS is live, whether the cache folder is writable, and which ambience files exist.

## Nothing breaks in front of Erika

Every external dependency has a floor under it:

| If this fails | You get |
|---|---|
| No LLM key, or the API errors | The template story, written from the same fifteen answers |
| A TTS model's daily quota is spent | The next TTS model, automatically |
| No voice key at all, or TTS errors | The browser's own voice, still paced to the reveal |
| Narration comes back incomplete | Nothing cached, browser voice instead — a story with a hole in it never ships |
| Voice finishes rendering after the reveal started | A quiet "studio voice is ready" prompt; "Read it again" plays it |
| No speech synthesis at all | A silent, time-paced reveal — the words still land in rhythm |
| No ambience mp3s | Beds synthesised from filtered noise in the browser |
| `audio/generated/` not writable | Browser voice, and `status.php` says exactly why |
| PHP missing entirely (opened as a file) | The story is composed client-side |

## Editing the story

Two places, and they should stay in step:

- `story_system_prompt()` in `api/lib.php` — the prompt sent to the LLM.
- `fallback_story()` in `api/lib.php` and `localStory()` in `js/story.js` — the template used when there's no key.

The contrast beat ("Three years ago, I would have…") is the emotional centre of
the whole product. It's the reason question 15 exists. Don't cut it.
