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
logo lockup. **This needs exported assets from Erika** — ideally an SVG of the
doorway E plus a 1024×1024 PNG; I can generate every size from those but cannot
draw the mark.

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

### Phase D — brand

9. **Icons and logo** once Erika supplies the doorway E as SVG + 1024px PNG:
   regenerate `public/icons/*`, swap the topbar wordmark for the lockup, add the
   tagline to the landing page, login and register screens.

### Also, before real testers arrive

- **Turn on `REQUIRE_VERIFICATION`.** It is still off, and it was only ever off
  because mail did not send. Mail sends now.

---

## Verification

- `php artisan test` — the suite is at 277 after Phase B; Phase C adds to it.
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
