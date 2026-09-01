# Where things stand, and what to do next

Written at `0014262`, deployed and live. This is the handover: read it first,
then [`LAUNCH-PLAN.md`](LAUNCH-PLAN.md) if you need the reasoning behind a
decision rather than the state of it.

---

## Live right now

- `escalate.cloud` serves `0014262`. 343 tests passing.
- Public pages are **Ivory** — warm ivory ground, aubergine text. Body text
  measures 14.37:1, muted 5.34:1, on a 390×844 phone.
- Erika's doorway E is the mark, everywhere: public pages, signed-in topbar,
  every admin page, and the admin door.
- Invite-only is **on**. Email verification is **off**.
- Stripe is in **test** mode.

---

## Next, in the order I would do them

### 1. Brand colours, editable from the admin — small, and asked for

Erika's eight brand colours live in `config/escalate.php` under `escalate.brand`
and are read by the theme blocks in `public/css/app.css`. **None of them is in
`Settings::editable()`**, so changing a hex is still a code change and a deploy —
which during a launch is the wrong shape, and is exactly the problem her copy
fields already solved.

- Add them to the `'How it looks'` group as `type => 'text'`, the same type
  `Words on the public pages` uses. Config defaults stay the source of truth; an
  admin row is a change of mind.
- **`escalate.themes_offered` is also missing from the allowlist** — found while
  writing this. `Theme::offered()` exists and is tested, including the guard that
  an admin cannot hide a theme somebody is already using, but nothing in the
  admin is wired to it. Same section, same fix, do them together.
- **The caution that makes this worth doing carefully:** a colour box Erika can
  type into is a colour box that can be made unreadable. Each field should render
  a live swatch and a contrast reading against its own ground, so a bad value is
  visible before it is saved rather than discovered by a tester.

### 2. Per-screen light and dark — the largest piece of the brand brief

Erika asked for everyday screens light, immersive screens dark, and the app does
not do this. `Theme::forUser()` is four lines — the person's chosen theme, else
the public one — with no notion of which screen it is on. The string `immersive`
appears in no view.

Both ends already exist: Ivory and Aubergine are complete palettes. What is
missing is the thing that chooses between them.

- `@section('surface', 'immersive')` on the reading, narration and Rewind views;
  `Theme::forUser()` becomes surface-aware and resolves those to Aubergine while
  everything else stays on Ivory.
- Champagne (`--champagne`) gets bound to the remaining achievement states. It is
  currently read by exactly one thing, `.pill-founding`.
- **Decide deliberately whether a person's own theme choice still wins.** It
  currently beats the public setting, and it is not obvious that it should beat a
  per-screen decision — a light theme on a bedtime listening screen defeats the
  point of the screen. This is a product question, not a technical one; ask.

### 3. A logo uploader — real work, and not urgent

Asked about, not built. There is **no file upload anywhere in the app** — zero
`type="file"` inputs, no controller that handles an uploaded file. Escalate has
never accepted a file from anyone.

If it gets built, the two things that will bite:

- **`public/brand/` is baked into the Docker image and wiped on every deploy.**
  An uploaded logo has to live on the persistent volume the SQLite database
  already uses, and be served through a route, or it silently vanishes on the
  next ship.
- **The container has no image library** — no Pillow, no ImageMagick, no sharp.
  The colourway derivation and icon generation done for the current mark were run
  through Chromium's canvas from this sandbox, which is not available to the app
  at runtime.

The cheap version that avoids both: admin uploads the files she already has —
one for light grounds, one for dark, one square for icons — and the app serves
what it is given, with no derivation. A quarter of the work and better results,
because her own export always beats a recolour.

---

## Waiting on a human, not on code

- **Turn on `REQUIRE_VERIFICATION`.** Still off, and it was only ever off because
  mail did not send. Mail sends now. This should happen before real testers
  arrive.
- **Erika's own aubergine logo export.** The dark-on-light mark currently shipped
  is her ivory artwork recoloured programmatically. It holds up at every size the
  app uses, but her own export from the brand sheet would be better. Dropping it
  in as `public/brand/mark-aubergine.png` is a one-file swap — nothing else
  changes, because the palette points at the filename.
- **DNS `A` record for `mail.escalate.cloud`**, and confirm inbound port 25 is
  open, before self-hosted mail can receive.
- **Regenerate the ranch reading** on the live app. The fix for readings naming
  people who do not exist shipped in `284525c`; that specific reading is still
  the ruined one, and `stories.regenerate` on the reading screen replaces it.
  It is the proof the fix worked.
- **Stripe is in test mode.** Live keys and the webhook still to be set up before
  anybody can actually pay.

---

## Things that will waste your time if you do not know them

**Deploying.** Connection details are in [`../COOLIFY.md`](../COOLIFY.md), and
have been rediscovered by guesswork twice — read the file rather than trying a
plausible hostname. Two specifics:

- The deploy endpoint is **POST**. `GET` returns
  `{"message":"This endpoint has changed to a POST request."}`.
- Coolify tracks `claude/escalate-app-bugs-l73sgw`. Pushing anywhere else does
  not reach production, and a redeploy will happily rebuild the same old commit
  and look successful.

**A stored setting beats the config default.** `Settings::apply()` overlays
database rows onto config at boot. Changing a default in `config/escalate.php`
and deploying does *not* change a value that has been saved in the admin panel —
this cost a cycle when the public theme stayed Amethyst after Ivory shipped as
the default.

**This sandbox cannot browser-test the live site.** Chromium reaches no external
host — verified against `example.com`, which fails identically. `curl` works
fine. So live verification is `curl` and reading HTML; browser checks run against
a local server:

```sh
php artisan serve --host=127.0.0.1 --port=8112     # with a throwaway DB
# Chromium at /opt/pw-browsers/chromium, PLAYWRIGHT_BROWSERS_PATH is preset
```

Do not imply a live page was seen in a browser. It was not.

**The QA account is not an admin on production.** `/admin` and `/admin/login`
both 404 for it, which is the app working as designed — the admin door is
invisible to non-admins. Admin changes on production need Erika or a real admin
account.

**No image library in this sandbox either.** Pixel work goes through Chromium's
canvas. Working scripts from the logo change are the pattern to copy: probe
alpha and bounds, recolour, then render a sheet and *look at it*. Note that a
`file://` image taints the canvas — inline it as a `data:` URI to read pixels.

---

## What shipped in the last session, briefly

`855d54b` Ivory and Aubergine palettes, `escalate.brand`, the iridescent
gradient spent on exactly two things. Three incidental fixes fell out of it:
Parchment had no `--ring` and was inheriting a focus ring nearly invisible on
cream; `--brass` was set to the champagne hex on the brand themes, putting the
reserved colour on every eyebrow in the app; and both new utilities were losing
to `.btn`/`.pill` on source order, so the iridescent button rendered flat.

`e2bfbb0` A hand-drawn doorway E, as an interim mark.

`43d3d18` `coolify.escaluxe.com` recorded as a dead hostname, after it cost a
deploy that was reported as blocked by the environment when it was not.

`0014262` Erika's real artwork replaces the drawing. Master in
`resources/brand/`, two colourways in `public/brand/`, `--mark-url` declared per
theme so the token-parity test catches a theme that forgets, icons regenerated,
lockups exported.
