# Escalate

A private manifestation journal. Somebody names what they want; the app writes it
back to them as an ordinary moment inside the life where they already have it,
reads it aloud, and keeps a record of what came true.

The app lives in **`escalate/`** — a Laravel 13.8 project on PHP 8.3+. The repo
root holds nothing but this file and that directory.

**Start here:** [`escalate/docs/NEXT.md`](escalate/docs/NEXT.md) — what is built,
what is next, and what is waiting on a human. Read it before planning anything.

---

## The shape of it

| | |
|---|---|
| Live | <https://escalate.cloud> (and `www.`) |
| Deployed branch | `claude/escalate-app-bugs-l73sgw` — **not** a default branch |
| Deploying | [`escalate/COOLIFY.md`](escalate/COOLIFY.md) — connection details, and two dead hostnames not to chase |
| Configuring the host | [`escalate/DEPLOY.md`](escalate/DEPLOY.md) |
| History and reasoning | [`escalate/docs/LAUNCH-PLAN.md`](escalate/docs/LAUNCH-PLAN.md) |
| Privacy and terms copy | [`escalate/docs/LEGAL-PACK.md`](escalate/docs/LEGAL-PACK.md) |

`CHANGELOG.md` and `README.md` inside `escalate/` are Laravel's own, untouched.
Nothing about this project is in them.

## No build step

There is no Vite, no npm, no asset pipeline. `public/css/app.css` is
hand-written and served as-is; `asset_v()` appends a content hash for cache
busting. Fonts are self-hosted in `public/fonts/`. **Do not introduce a build
step** to add a feature — everything so far has been done without one, and the
deploy is a plain PHP container because of it.

## Conventions that are load-bearing

Break these and something quietly stops being true:

- **Anything a person writes is encrypted at rest** via cast, keyed on `APP_KEY`.
  New tables holding user prose follow suit.
- **Ownership checks 404, never 403.** Whether a record exists is itself private.
- **`App\Support\Settings::editable()` is a security boundary**, not a
  convenience list. A key absent from the allowlist cannot be written from the
  admin panel even if a row for it exists.
- **Admin sees counts, never content.** An administrator supporting somebody must
  be able to see that they have written nothing without being able to read what
  they wrote. `Admin\DashboardController`'s docstring is the standing statement
  of this; the Beta screen follows it. Applications and feedback are the only
  exceptions, because those are addressed *to* the reader.
- **`AccountEraser` enumerates tables explicitly.** A new table that is not added
  there is a hole in both export and erasure.
- **CSP is `script-src 'self'`.** No inline event handlers, no CDN scripts.
  Admin-typed copy reaches public pages, so render it with `{{ }}`, never
  `{!! !!}`.

## Testing

```sh
cd escalate && php artisan test        # 343 passing
```

`RefreshDatabase` drops tables, so never point a test run at a database you care
about. Local browser checks run against `php artisan serve` with Chromium at
`/opt/pw-browsers/chromium` — see NEXT.md for the recipe and its limits.
