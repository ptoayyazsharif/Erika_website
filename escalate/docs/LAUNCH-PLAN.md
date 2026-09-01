# Escalate: launch notes vs. what is actually built

## Context

Erika has produced brand and launch materials for a private beta — logo concepts
(Concept 1, "The Threshold" doorway E, selected), a tagline, a 5-question
application form, "The Founding 25", a 7-day content calendar, a beta funnel and
a list of what testers are promised. This is a read of those notes against the
code as it stands at `271ccf5`, and a plan for the gap.

No code has been changed.

---

## Already built

| From the notes | Where it lives |
|---|---|
| AI-powered personalised stories | Desires → Stories, `StoryController`, Anthropic |
| Listen to them daily | Narrations, `MediaController@narration`, ElevenLabs |
| Gratitude journal | `GratitudeController`, full CRUD |
| Rewind your journey | `RewindController` + `JourneyController` |
| Private, secure & personal | Encrypted at rest, 404-not-403 ownership, CSP, `/privacy`, account export + delete |
| "Escalate is currently invite-only" | `INVITE_ONLY`, `Invite` model, admin Invites screen |
| Limited spots | `Invite::mint($email, $note, $expiresInDays)` — already takes an email and a note |
| Paid plans after beta | Stripe, plans, upgrade/downgrade, admin pricing |
| Email that reaches people | Resend, confirmed delivering |
| Installable app, live domain | PWA manifest, icons, `escalate.cloud` over TLS |

The product half of the notes is largely real. The **funnel** half is not.

---

## Missing

### 1. Daily Affirmation Cards — promised, not built

Listed under "WHAT YOU'LL GET IN THE BETA" and in the one-sentence description.
What exists is scaffolding only:

- `app/Models/Affirmation.php`, `app/Models/AffirmationSet.php`
- tables `affirmations`, `affirmation_sets`, and `profiles.affirmations_generated_at`
- `config/escalate.php:113` — `QUOTA_AFFIRMATION_SETS_PER_DAY`, default 2
- `Plan::flatQuota()` already answers for `'affirmations'`

There is **no controller, no route, no view and no generation**. `User::affirmations()`
is the only reference outside the models. A tester who reads the marketing and
opens the app will not find this feature.

Biggest single item on the list.

### 2. No public landing page

`routes/web.php:87` — `/` redirects to `/login`. There is no marketing surface at
all, so the funnel has no top. Day 6–7 of the content calendar ("Reveal the
brand", "Open applications") has nowhere to point.

### 3. No application form

The 5 questions exist only in Erika's document. No model, no route, no admin
screen. This is the first item under "IMPORTANT NOTES FOR MARC".

### 4. Nothing sends an email except password reset and verification

No `app/Mail/` or `app/Notifications/` directory exists. The notes call for an
automated thank-you on application and a notification on selection. Mail works
(Resend); nothing is wired to it.

### 5. No "Founding 25" badge

No column, no UI. `invites.note` could carry the cohort, but there is no badge on
the user and nothing renders one.

### 6. Beta metrics are not measured

`Admin\DashboardController` shows totals, active-in-7-days (by `last_login_at`),
invite counts, spend and ceilings. The notes measure something different:

- **Activation** — did they create their first story?
- **Habit** — did they come back the next day?
- **Emotional connection** — are they using narration, gratitude, rewinds?
- **Retention** — still engaged at 7–14 days?
- **Completion** — did they finish the 7-day test?

All answerable from existing tables (`stories`, `narrations`, `gratitude_entries`,
`rewinds`, `users.last_login_at`). None are asked today.

### 7. No day-7 feedback survey

Including the killer question — "How disappointed would you be if Escalate
disappeared tomorrow?" Nothing exists.

### 8. No waitlist

The funnel ends "Testimonials → Waitlist → Public launch". Nothing captures the
people who apply and are not selected.

### 9. Brand assets are the old ones

`public/icons/` holds favicon, apple-touch, 192/512 and maskable icons — none of
them the doorway E. The topbar wordmark is text (`Escalat<span>e</span>`), not the
logo lockup.

**Since resolved** — the mark was hand-authored to Erika's geometry rather than
waiting on an export. See "The new brand", step two.

### 10. The tagline appears nowhere

"Imagine it forward. Understand it backward. Remember what came true." is in no
view and no config.

---

## Plan

### Phase A — the funnel, so applications can open — **DONE**, live at `c253c0b`

1. **Public landing page** at `/` for guests, redirecting to `/today` when signed
   in. Brand story, tagline, the five beta benefits, the invite-only framing, one
   CTA to the application. Uses the existing design system in `public/css/app.css`
   — no new build step.
2. **Application form** — new `Application` model + migration (the 5 answers, name,
   email, status, decided_at). A public, unauthenticated, mail-sending, DB-writing
   endpoint, so it gets the same treatment as `/register` did: `throttle`,
   honeypot, `scalar_input()` on every field, and no response that reveals whether
   an address has already applied.
3. **Admin → Applications** — list, read one, Select / Decline. Select mints an
   invite via the existing `Invite::mint($email, "Founding 25")`, sets the
   comped plan override, and emails the code. Decline moves them to the waitlist.
4. **Two Mailables** — "we have your application" and "you're in, here's your
   code". First things in `app/Mail/`.

### Phase B — keep the promise — **DONE**, live at `6b5fca5`

5. **Daily Affirmation Cards** — controller, route, Today-screen card and a
   dedicated screen; daily set generated from the user's desires and world,
   through the existing `Services\Ai\Anthropic` client, honouring the
   `affirmations` quota that `Plan::flatQuota()` already answers for. Fills in the
   scaffolding rather than starting fresh.
6. **Founding 25 badge and pricing** — `users.cohort` (nullable string), set when
   an invite minted with that note is claimed; a small pill in the app.
   "Special pricing for life" is honoured with the existing `plan_override`
   field: all 25 comped permanently on selection. No new billing code, no Stripe
   coupon to maintain, reversible per person, and they never meet a card form.

### Phase C — run the beta with numbers — **NEXT**

#### 7. A record of which days somebody was active

Erika's scorecard asks "did they come back the next day?" and "still engaged at
7–14 days?". Neither is answerable today: `users.last_login_at` is a single
timestamp, overwritten on every sign-in, so it can say when somebody was last
here and nothing about the shape of their week.

- Migration `activity_days`: `user_id`, `day` (date), unique on both, index on
  `day`. One tiny row per person per day.
- Middleware `App\Http\Middleware\RecordActivityDay`, appended to the `web`
  group in `bootstrap/app.php` alongside `SecurityHeaders`. Signed-in GET
  requests only. Guarded by a cache key per user per day so it is one
  `insertOrIgnore` a day rather than one a request, and wrapped so a write
  fault can never cost somebody their page.
- Chosen over deriving activity from content timestamps because somebody who
  returns daily to *listen to and re-read* their stories is exactly the user a
  journal must not score as absent.
- **`App\Services\AccountEraser` must delete and export these rows too.** It
  enumerates tables explicitly; a new one that is not added there is a hole in
  both erasure and export.

#### 8. Admin → Beta

New `Admin\BetaController` + `resources/views/admin/beta.blade.php`, added to the
admin nav in `resources/views/layouts/app.blade.php`. Erika's five measures, per
tester and in aggregate:

| Measure | Answered by |
|---|---|
| Activation | has at least one story |
| Habit | active on a day after the day they joined |
| Emotional connection | has used narration, gratitude, a rewind, or kept a card |
| Retention | active within the last 7 days; still active 7–14 days in |
| Completion | 4 or more active days inside their first 7 — Erika's own bar |

- Aggregate tiles first, then a per-person table; `withCount` as
  `Admin\UserController@index` already does, so it stays one query per column
  rather than N+1.
- Filterable by cohort, defaulting to Founding 25, since that is the group under
  test — but the rest stay visible.
- **Counts only, never content.** `Admin\DashboardController`'s docstring makes
  this the standing rule for the admin area: an administrator supporting
  somebody must be able to see that they have written nothing without being able
  to read what they wrote. Nothing on this screen decrypts anything.

#### 9. The day-7 survey

The Sean Ellis product-market-fit set, which is where Erika's killer question
comes from, so the answers are comparable to other products rather than only to
themselves:

1. How would you feel if you could no longer use Escalate? *(very disappointed /
   somewhat disappointed / not disappointed)* — the killer question
2. What type of person do you think would benefit most from Escalate?
3. What is the main benefit you get from it?
4. How can we improve it for you?

- Migration `feedback_responses` + `App\Models\FeedbackResponse`: one row per
  user, `disappointment` in plaintext (it is the scored answer and needs
  grouping), the three prose answers **encrypted** like every other thing a
  person writes here. `$fillable = []` and `forceFill`, matching `Application`.
- `FeedbackController` at `/feedback`, behind `auth` and `not-suspended`.
- Invited by a dismissible nudge on Today from day 7 (`created_at` older than 7
  days, no response yet). "Not now" hides it for that session only — never a
  wall in front of somebody's journal.
- `Admin\FeedbackController` lists responses and reports the PMF score: the
  share answering "very disappointed", the number that matters.
- These answers are content, but content *addressed to* the person reading them
  — the same footing as an application, and unlike a journal entry, which is why
  admin may read them at all.

### Urgent — the reading that named a stranger

**Context.** A desire naming nobody ("Whole family together enjoying in ranch,
all brothers and their kids live at same place") produced a reading in which
*"Zarak calls out from the second house."* There is no Zarak in the account —
not in My Circle, not anywhere. For a private journal whose whole promise is
that it writes back *your* life, a stranger standing in it is the worst class of
bug this app can have.

It was not a hallucination out of nowhere. **The prompt ordered it.**

#### Root cause

`app/Services/StoryWriter.php`, `system()`, rule 4:

> Every place, number and object they gave you must appear at least once,
> spelled exactly as they wrote it, **and so must the names of other people in
> their life.** Do not invent names for people they did not name.

Two clauses in direct contradiction, and the contradiction goes live *precisely
when the user has named nobody*: names must appear, and there are none to use.
The model resolved it the way this same file's docstring records the perspective
rule being resolved — the MUST won, so it manufactured a name to satisfy it. The
desire mentions brothers; it produced a brother.

The same rule fails on the opposite input. `StoryWriter::user()` sends the
**entire** My Circle, unscoped, under "use these names exactly" — so a desire
about a new job is required to mention a daughter. `people_involved` (the
checkbox list of circle members on the desire form,
`resources/views/desires/create.blade.php:92`) is passed to the prompt but never
used to decide who is *sent*.

`app/Services/RewindWriter.php` rule 6 already states the correct rule — "Name
the people they named, exactly as written, and only those." StoryWriter is the
outlier. **`app/Services/AffirmationWriter.php` copied StoryWriter's unscoped
circle block when I wrote it in Phase B, so the cards carry the same fault.**

#### Fix

1. **Split rule 4.** Places, numbers and objects keep their "must appear" —
   that clause is what stops a reading sounding generic and it is doing its job.
   Names become permission, never obligation. When nobody is attached the rule
   is positive and concrete rather than a prohibition: *nobody is named; people
   appear by relationship* — "my brother", "his eldest", "the neighbour". The
   model already proved it writes this register well in the very same piece —
   *"one of my brothers' kids is arguing with a dog"* is exactly right — and it
   only reached for a name because it was told one had to appear.
2. **Scope the circle to `people_involved`.** Anyone not attached to this desire
   is not sent at all. A name that never reaches the prompt cannot reach the
   prose, which is a stronger guarantee than any instruction.
3. **The same two changes in `AffirmationWriter`**, which has the same block.
4. **Check before storing.** The writer asks for a declared list of everyone the
   piece names, verifies it against the allowlist, and treats a name outside it
   as a failed generation — one retry, then a visible failure rather than a
   stored reading with a stranger in it. Parsed and stripped exactly like the
   `(DESIRE n)` tag in `AffirmationWriter::parse()`.

   **This is a net, not a proof.** A model that names someone and omits it from
   its own declaration still gets through. The prompt is the fix; the check
   catches the case where the model is honest but wrong, and every circle name,
   which is an exact string comparison.

#### Status — written and tested, not shipped

The four changes are **applied to the working tree and pass their own tests**;
nothing is committed or deployed.

- `app/Services/StoryWriter.php` — rule 4 split so places/numbers/objects keep
  their "must appear" and names do not; new `namingRule()` and `peopleFor()`;
  circle filtered to `people_involved`; the loose "People involved" context line
  removed so who may be named has exactly one home; `NAMES:` declaration added
  to the output contract, with `splitNames()` stripping it and
  `refuseStrangers()` throwing on anyone off the allowlist.
- `app/Services/AffirmationWriter.php` — same scoping and its own
  `namingRule()` / `namedAcross()`.
- `tests/Feature/NamingTest.php` — new, **7/7 passing**, asserting the prompt
  itself: the ranch desire with nobody attached forbids naming anyone, the old
  mandate is gone rather than softened, unattached circle members never reach
  the prompt, attached ones do, a declared stranger is refused and not stored,
  and the `NAMES:` line never reaches a reader.

`WriteStory` needed no change: an exception from the writer already retries once
(`$tries = 2`) and then lands in `failed()`, which marks the reading failed with
a plain message — one retry, then a visible failure, which is what this wants.

**Not yet done, and the reason this is not finished:**

1. **The full suite has not been run since the change.** `GenerationTest`,
   `CircleTest`, `BlankFieldsTest` and `CeilingTest` all exercise this path.
   Nothing asserts the removed "People involved" line — checked — but that is an
   argument for expecting it to pass, not evidence that it does.
2. Commit and deploy.
3. Then regenerate the ranch reading on the live app, as the proof.

#### Afterwards

The ruined reading can be replaced from the app — `stories.regenerate` already
exists on the reading screen. Worth doing on this desire specifically, as the
proof the fix worked.

**One limit stated plainly:** a model that names somebody and leaves them out of
its own `NAMES:` line still gets through. There is no reliable way to find an
arbitrary invented name in prose, so the prompt is the fix and the check is a
net. What the net catches for certain is every circle name, which is an exact
comparison, and the honest-but-wrong case — which, on the evidence of the
reading that started this, is the one that actually happens.

### Erika's copy, and putting it under her control

**Context.** Erika has rewritten the public wording and adjusted three of the
five application questions. Right now every one of those strings is hardcoded in
a Blade template, so each revision is a code change and a deploy — which for
marketing copy, during a launch, is the wrong shape entirely. She has asked for
the copy to be editable from the admin panel, and she is right to.

#### The new wording, verbatim

Intro: *"A private AI-powered personal growth experience that turns your goals
into personalized stories you can read and listen to—while helping you reflect
on your progress, gratitude and wins along the way."*

Below it: *"Escalate hasn't been publicly launched yet. We're inviting a small
group of founding testers to experience it first and help shape what it
becomes."*

Questions intro: *"Five quick questions. There are no right answers—we're
looking for a diverse group of thoughtful testers who will actually use Escalate
and tell us what they think."*

- **Q1** *"What area of your life are you currently focused on changing,
  improving or creating something new in?"* — helper: *"Career, money,
  relationships, health, family, lifestyle, personal growth—or anything else
  that matters to you."*
- **Q2** *"Do you currently use any reflection or personal-growth practices—such
  as journaling, visualization, prayer, meditation or affirmations?"* — helper:
  *"If yes, tell us what you use and what you like about it. If not, that's
  useful for us to know too."*
- **Q3** *"Have you ever used a manifestation, visualization, journaling or
  personal-development app? If so, which one—and what did you like or dislike
  about it?"*
- **Q4 and Q5** unchanged.

Her outreach message ("I've been quietly developing…") is not a page in the app,
but it is the thing she will paste into DMs all week, so it gets a home too —
stored alongside the rest and shown on **Admin → Invites**, where somebody is
already standing when they need it.

#### How

1. **A `copy` block in `config/escalate.php`** holding every one of these
   strings as its default. Erika's wording is the default, so a fresh install
   and an untouched database both say exactly what she wrote — the admin
   override is a change of mind, not the source of truth.
2. **The views read `config('escalate.copy.*')`** — `resources/views/landing.blade.php`
   and `resources/views/apply.blade.php`, whose questions stop being a literal
   array in the template. `Admin\ApplicationController`'s answer labels and the
   `changing.required` validation message in `ApplicationController` read from
   the same place, so a reworded question cannot drift from the label above the
   answer it produced.
3. **A "Words on the public pages" group in `Settings::editable()`** — the
   allowlist stays the security boundary, exactly as for every other setting.
   It needs one new type, `text`, rendered as a textarea in
   `resources/views/admin/settings.blade.php`; `SettingsController::update()`
   needs no branch for it, since its default path already trims a string.
4. **Rendered with `{{ }}`, never `{!! !!}`.** Copy an administrator types is
   still untrusted input by the time it reaches a public page, and this app has
   a `script-src 'self'` CSP precisely so that a mistake here is not the last
   line of defence.

#### The one wrinkle worth stating

Answers already given were given to the *old* question. Editing a question later
re-labels answers that were written to different words. For a beta of 25 that is
acceptable and not worth a versioning table — but the admin field carries a line
saying so, because discovering it from confusing data later is worse than
reading it now.

### The public pages get a colour, and Erika gets the admin panel back

**Context.** Two asks, unrelated except that both are about somebody being able
to change something without me.

The **public pages** — landing, apply, login, register, privacy, offline, all
six through `resources/views/layouts/auth.blade.php` — are always Midnight navy.
Not by choice: `active_theme()` in `app/Support/helpers.php` reads the signed-in
user's `profiles.theme` and falls back to a hardcoded `'midnight'`, and a guest
has no profile. So the first thing anybody ever sees is a colour nobody picked,
and Erika cannot change it.

And **Admin → Settings is now thirteen groups on one page** — Writing, Voice,
Billing, three Stripe groups, Mail, two copy groups, sign-up, three limit
groups. It has grown with every phase and it is genuinely daunting to open.

#### Themes

Six exist already (`config/escalate.php` → `themes`): Midnight, Ember, Tide,
Graphite, Parchment, Linen. Adding one is a config key plus a
`[data-theme='key']` block in `public/css/app.css` — that is all, and the config
comment already says so.

**Two purple ones**, per the request:

- **Amethyst** (dark) — deep aubergine ground, soft violet accent, a warm
  off-white for text. Reads premium and calm rather than pink.
- **Wisteria** (light) — pale lilac-grey paper, deep plum ink, muted violet
  accent. The light counterpart, so the pair toggles between each other the way
  Midnight/Parchment already do.

Both stay inside the brief the config states: *premium, calm, gender-neutral, no
pinks, no wellness pastels*. Purple is compatible with that at low saturation
and high depth; it stops being so the moment it brightens toward magenta, which
is the line these two sit well behind.

Each also needs its `body::before` gradient block, as Ember and Tide have, or
the ambient wash silently reverts to Midnight's.

#### One setting, covering every public page

`escalate.public_theme`, exposed in admin as a dropdown of every theme. The
hardcoded `'midnight'` in `active_theme()` becomes that config value; the
signed-in path is untouched, so a person's own choice still wins over it.

Because all six public pages already extend one layout, this is a single change
that colours the entire public site.

A second setting, `escalate.themes_offered`, controls which themes appear in the
user's picker in My World — useful, but secondary to the public one, and it must
never hide a theme somebody is already using out from under them.

#### Breaking up Settings

Sections, each its own page at `/admin/settings/{section}`, with the existing
`/admin/settings` becoming a short index that names them:

| Section | What is in it |
|---|---|
| Look | Public theme, which themes people can pick |
| Words | The public copy, and the five questions |
| Who gets in | Invite-only, confirmed email |
| Limits | Per person, per plan, the ceiling |
| Money | Billing, Stripe mode, test keys, live keys |
| Mail | Provider details, the test-send button |
| Writing and voice | The Anthropic and ElevenLabs keys and models |

`Settings::editable()` keeps returning groups; a `sections()` map alongside it
says which groups belong to which section, so nothing about the allowlist —
which is the security boundary — changes shape.

**The trap this must not fall into.** `SettingsController::update()` iterates the
whole schema, and for `bool` and `mode` it treats *absence* as off:

```php
Settings::put($key, array_key_exists($field, $posted) ? '1' : '0', ...)
```

That is correct for one page holding every checkbox. Split the page naively and
saving *Mail* silently switches off invite-only, email confirmation and billing,
because none of their checkboxes were on the form. The fix is to post the
section and scope the loop to that section's fields — and it gets a test of its
own, because the failure is silent, destructive, and would look exactly like a
setting Erika swears she never touched.

### The new brand — apply site first, because ten people are waiting

**Context.** Erika has issued a full brand direction: a warm-neutral palette
with violet iridescence, a per-screen colour strategy, and the doorway E worked
out in detail. She also said the highest priority is having the apply site ready
for ten people who are waiting. Those two facts set the order below.

#### The palette

```
Warm Ivory      #F4F0E8      Deep Aubergine  #241D2B
Mushroom Taupe  #C9BFB2      Royal Violet    #6946A2
Cocoa Taupe     #74675F      Electric Iris   #8B6FE8
Champagne       #C7A86B      Indigo Blue     #4058A6

Iridescent      #9A7CF0 → #7456C7 → #4966B3
```

The part that does not fit the current architecture: **this is not one theme.**
Escalate picks a single theme and paints the whole app with it. Erika is asking
for everyday screens light, immersive screens dark, iridescence only at
transformation moments, and champagne only where something was earned. That is
a per-surface decision, not a per-user one, and it is the real work in this
brief. It is also not what the ten people need today.

#### Step one — the public pages — **DONE**

`/`, `/apply`, `/login`, `/register`, `/privacy` all run through
`resources/views/layouts/auth.blade.php`, and `escalate.public_theme` already
decides their colour, so this was a new theme plus one setting.

- **Ivory** and **Aubergine** added to `config/escalate.php` and
  `public/css/app.css`, exactly as Amethyst and Wisteria were. `public_theme`
  now points at Ivory.
- The iridescent gradient as a `--iridescent` token, spent in exactly two
  places: the apply page's submit button and the landing page's "Getting in"
  rule. Those are the threshold; nothing else on the public site is one.
- `escalate.brand` holds the eight named colours, so the admin fields planned
  below have somewhere to write to.

**Three things this turned up that were not in the brief:**

1. **Parchment declares no `--ring`.** It inherited `:root`'s, which is built
   from a pale sage picked to glow on ink navy and which all but disappears on
   cream — so the focus ring on a whole theme was close to invisible. Found by
   widening the token-parity test from a hardcoded list of themes to every
   theme in config. Given its own ring in its accent.
2. **Champagne was the colour of everything.** Ivory set `--brass` — which
   paints eyebrows, the tab indicator, today's dot and progress bars — to the
   champagne hex, so the reserved colour was on every screen. `--brass` is now
   cocoa taupe on Ivory and mushroom on Aubergine; champagne lives in
   `--champagne` and is read by one thing, the Founding pill.
3. **The iridescent button rendered flat.** `.btn-iridescent` is declared beside
   the tokens that explain it, 445 lines above `.btn`; at equal specificity the
   later rule wins. Doubled to `.btn.btn-iridescent`. This is the failure mode
   worth remembering: it does not look broken, it looks deliberate.

#### Step two — the doorway E — **DONE**

Hand-authored SVG at `public/brand/mark.svg` and `public/brand/lockup.svg`, to
Erika's geometry: three strokes, bottom one longest and lifting at the tip,
middle one shortest, doorway as an arch in the **lower** counter with the
iridescent gradient through it and a spill on the floor.

The door is in the lower counter because the first attempt put it mid-letter,
where it ate the middle arm and turned the whole mark into a bracket with a lamp
in it. The bottom arm is the most emphatic stroke because the one failure
available to this mark is reading as an F, and an F has no bottom arm.

- `resources/views/partials/mark.blade.php` inlines it in `currentColor`, so one
  file serves ivory-on-aubergine and aubergine-on-ivory. Gradient ids are
  suffixed per render: two marks on a page sharing an id means the second paints
  with the first one's gradient.
- On the public pages as a lockup above the heading; in the signed-in topbar
  beside a tracked-wide ESCALATE, replacing `Escalat<span>e</span>`.
- `public/icons/*` regenerated from it — aubergine ground, ivory ink, maskable
  variants held inside the 80% safe circle. The manifest's ground moved from
  Midnight navy to aubergine to match.
- Rendered at 32/48/64/120/192/512 on both grounds and **looked at**, because
  the previous concept failed by reading as an F and no assertion catches that.

**Stated plainly:** this is a clean geometric letterform, not a drawn
high-contrast serif. It is good enough to launch behind and swaps out for a
designer's file without touching anything else. Typography stays Lora over
Raleway — already self-hosted, no build step.

#### Step three — the per-screen strategy — **NOT STARTED**

`Theme::forUser()` becomes surface-aware: `@section('surface', 'immersive')` on
the reading, narration and Rewind views resolves to Aubergine while everything
else stays on Ivory. Champagne gets bound to the remaining achievement states.
The largest piece and the least urgent.

#### Admin — **NOT STARTED**

The brand colours as `text` fields under **How it looks**, so Erika can adjust
hexes without a deploy — the same pattern as her copy, config defaults
remaining the source of truth. Each field renders a live swatch and a contrast
reading against its own ground, because a colour box she can type into is a
colour box that can be made unreadable.

#### What is deliberately not here

No crowns, butterflies, stars, moons, crystals or lotus flowers, and no
inheriting Escaluxe's black/cream/gold.

### Also, before real testers arrive

- **Turn on `REQUIRE_VERIFICATION`.** It is still off, and it was only ever off
  because mail did not send. Mail sends now.

---

## Verification

### The name fix

The prompt is the thing that broke, so the prompt is what gets asserted — built
and inspected directly, not inferred from output:

- With nobody attached to the desire: the built prompt contains the "nobody is
  named" instruction, contains **no** circle name, and contains no clause
  requiring a name to appear.
- With two circle members attached: exactly those two appear, and a third
  circle member who was not attached does not.
- Regression, named for what happened: a desire whose text mentions "brothers"
  and whose `people_involved` is empty must produce a prompt that forbids
  naming anyone.
- With the Anthropic client faked to return a piece naming somebody outside the
  allowlist: nothing is stored, and the reading ends in a visible failure.
- The same three assertions against `AffirmationWriter`.

### Erika's copy

- The landing page and the application form serve her exact wording out of the
  box, with no settings row present — defaults are the source of truth.
- Saving an override in admin changes what the public page serves.
- Copy containing `<script>` comes back escaped on the live page, never raw.
- A reworded question and the label above that answer in Admin → Applications
  stay in step, because both read the same key.

### Themes and the settings split

- A guest on `/`, `/apply` and `/login` gets the theme Erika chose, and changing
  it in admin changes all three.
- A signed-in person's own theme still beats the public setting.
- Both purple themes declare every token their siblings do — asserted by
  comparing the custom properties in each `[data-theme]` block against
  Midnight's, so a half-finished palette fails rather than silently inheriting.
- Each theme's `scheme` and `chrome` reach `<html>` and the `theme-color` meta,
  so browser chrome follows the page.
- **The destructive one:** saving the Mail section leaves invite-only, email
  confirmation and billing exactly as they were. This is the test that matters
  most in the whole change.
- A theme somebody is already using cannot be hidden from them by an admin
  un-ticking it.

### The new brand

- **The gate for step one, met:** `/`, `/apply` and `/login` loaded in Chromium
  at 390×844 serve `data-theme="ivory"` — warm ivory ground, aubergine ink, no
  horizontal scroll. Body text 14.37:1, muted text 5.34:1, both measured with
  the alpha composited against the ground rather than off the raw channels,
  which reported 14.37:1 for the muted colour too and was simply wrong.
- Ivory and Aubergine declare every token Midnight does — and so does every
  other theme, since that test now iterates config rather than a written list.
  That is what turned up Parchment's missing focus ring.
- The doorway E rendered at 32/48/64/120/192/512 on both grounds and looked at.
- `--brass` is not the champagne hex on either brand theme, and exactly one
  thing reads `--champagne`. Asserted against the palette rather than by
  grepping templates: the grep version passed while every eyebrow on the public
  site was gold, which is worse than having no test at all.
- Both brand utilities are written doubled, so they outrank the components they
  are declared above. Verified in a browser: the apply button's computed
  `background-image` is the gradient, not `none`.

### Everything else

- `php artisan test` — 343 passing.
- **Phase C specifically:**
  - Activity days: a second request the same day writes no second row; a request
    the next day does; the middleware never breaks a page when the write fails;
    `AccountEraser` removes them and includes them in the export.
  - Metrics: build a tester with a known shape — joined 10 days ago, active on 5
    of the first 7, one story, one narration — and assert each of the five
    measures reads correctly off it. Assert an inactive person is not counted
    activated.
  - Survey: not offered before day 7, offered after, not offered twice, one row
    per person, prose answers encrypted at rest, and the PMF score counts only
    "very disappointed".
  - Ownership, as everywhere: one person cannot read or answer for another, and
    the whole Beta and Feedback area is a 404 to a non-admin.
- Browser-driven pass on a local server at the deployed commit
  (`tests/browser/journey.mjs` pattern, Chromium at `/opt/pw-browsers/chromium`),
  clicking the nudge through to a submitted response and reading it in admin.
- Live `curl` checks against `escalate.cloud` to confirm production serves the
  same commit; the sandbox cannot drive a browser against the live host.
