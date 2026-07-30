# Deploying Escalate to cPanel

Read the first section before anything else. Getting it wrong exposes every
user's journal in plaintext, and it is the easiest mistake to make.

---

## 1. The document root — the one that matters

**Point the domain's document root at `escalate/public`.** Not the repository
root, not `escalate/`.

cPanel → Domains → your domain → Document Root → `.../escalate/public`

Why this is the critical step: the repository root already contains an
`index.html`, so the natural instinct is to point `public_html` at the repo and
reach the app at `/escalate/`. Apache would then serve, as plain downloadable
files:

| URL | What it hands over |
|---|---|
| `/escalate/.env` | Both API keys **and `APP_KEY`** |
| `/escalate/database/database.sqlite` | The whole database |
| `/escalate/storage/logs/laravel.log` | Every stack trace |
| `/escalate/storage/app/escalate/audio/…` | Every user's narration, ownership check bypassed |

Apache does not block `.env` by default — only `.ht*` files.

And this is the one bug that defeats the encryption. Every private field in this
app is encrypted at rest, but `APP_KEY` is the key and it lives in `.env`. Anyone
who downloads `.env` *and* the database from the same exposed tree has plaintext
for every journal in the system. The encryption is worth exactly nothing against
this single misconfiguration.

`escalate/.htaccess` denies everything as a second layer, but **do not rely on
it** — it only helps if `mod_authz_core` behaves as expected. After deploying,
verify:

```bash
curl -i https://your-domain/escalate/.env          # expect 403 or 404
curl -i https://your-domain/.env                   # expect 403 or 404
curl -i https://your-domain/storage/app/escalate/  # expect 403 or 404
```

If any of those returns file contents, stop and fix the document root before
letting anyone sign up.

Also move the database out of the served tree entirely, or use MySQL (below).

---

## 2. Production environment

Copy `.env.example` to `.env` and set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
APP_KEY=                      # php artisan key:generate

LOG_LEVEL=error
LOG_STACK=daily               # 'single' never rotates and will fill your quota

SESSION_SECURE_COOKIE=true    # without this the session cookie has no Secure flag
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict

DB_QUEUE_RETRY_AFTER=300      # must exceed the longest job timeout (240s)

ANTHROPIC_API_KEY=sk-ant-…
ELEVENLABS_API_KEY=sk_…
```

Then:

```bash
chmod 600 .env
composer install --no-dev --optimize-autoloader
php artisan key:generate        # only if APP_KEY is empty — see the warning below
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> **Never regenerate `APP_KEY` on a database that already holds data.** Every
> encrypted field becomes permanently unreadable. There is no recovery.

`--no-dev` matters: `filp/whoops` is a dev dependency, and Whoops' error page
dumps `$_ENV` — which contains your API keys. With `--no-dev` installed *and*
`APP_DEBUG=false`, neither the keys nor a stack trace can reach a visitor.

### If you put Cloudflare (or any CDN) in front

Set `TRUSTED_PROXIES` to its IP ranges — and **never to `*`**. Trusting every
proxy means `$request->ip()` becomes whatever the caller puts in
`X-Forwarded-For`, which silently disables the login lockout, the registration
throttle, the generation limits, and the lockout on the password confirmation
that guards data export and account deletion.

"It's only reachable through the CDN" does not save you: Symfony takes the
leftmost `X-Forwarded-For` entry, and Cloudflare *appends* rather than replaces,
so a forged left-hand value still wins. On shared hosting the origin is usually
still reachable by IP anyway.

Left empty, the app uses the real `REMOTE_ADDR`, which is correct for a
directly-served cPanel origin.

### Database

SQLite works, but on cPanel `/home` is often NFS-backed where SQLite's locking is
unreliable — and sessions, cache, rate-limit counters and the queue all write to
that one file. Under trivial load you get intermittent `database is locked`
500s. `busy_timeout` and WAL are configured to soften it, but **MySQL is the
better choice here** and cPanel gives you one for free:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=cpaneluser_escalate
DB_USERNAME=cpaneluser_escalate
DB_PASSWORD=…
```

If you stay on SQLite, put the file outside the web tree:
`DB_DATABASE=/home/USER/private/escalate.sqlite`

---

## 3. The queue worker — without it, nothing generates

Story and narration generation are queued jobs. With no worker running, every
reading sits at "queued" forever and the reveal screen polls indefinitely. It
looks exactly like the AI being broken.

Add this cron entry (cPanel → Cron Jobs, every minute):

```
* * * * * /usr/local/bin/flock -n /home/USER/escalate-queue.lock /opt/cpanel/ea-php83/root/usr/bin/php /home/USER/path/to/escalate/artisan queue:work --stop-when-empty --max-time=55 --tries=2 --memory=128 >> /home/USER/logs/queue.log 2>&1
```

Details that matter:

- **Use the explicit PHP 8.3+ binary path.** cPanel's `php` on `PATH` is often
  older than the version serving the site. If it is below 8.3 the worker dies on
  a syntax error while the website works fine — a genuinely confusing failure.
  Find yours with `ls /opt/cpanel/ea-php*/root/usr/bin/php`.
- **`--stop-when-empty --max-time=55`** keeps each run inside its minute. Do not
  run a long-lived `queue:work` from cron on shared hosting; GoDaddy kills long
  CLI processes.
- **`flock -n`** stops overlapping runs. `NarrateStory` has a 240s timeout and
  can outlive its window.
- **`--memory=128`** — narration holds the whole mp3 in memory. Fine at 128 MB;
  some shared hosts default to 64.

Add a weekly cleanup so failures don't accumulate:

```
0 4 * * 0 /opt/cpanel/ea-php83/root/usr/bin/php /home/USER/path/to/escalate/artisan queue:prune-failed --hours=168
```

---

## 4. Permissions

```bash
find storage bootstrap/cache -type d -exec chmod 755 {} \;
find storage bootstrap/cache -type f -exec chmod 644 {} \;
chmod 600 .env
```

Never `777`. On a shared host that is readable and writable by other tenants.

No `php artisan storage:link` is needed. This app deliberately has no public
storage symlink — private files are streamed by an authorising controller
instead, and creating the symlink would only add an attack surface.

---

## 5. Making an admin

There is no web path to admin privilege, by design. Register normally, then at a
shell:

```bash
php artisan escalate:make-admin you@example.com
```

Admin is a *second* door: sign in as usual, then confirm your password again at
`/admin/login`. To a non-admin, both `/admin` and `/admin/login` return 404 —
the area is invisible, not merely forbidden.

---

## 6. After deploying — verify, don't assume

```bash
curl -i https://your-domain/escalate/.env      # 403/404
curl -sI https://your-domain/login | grep -i strict-transport   # HSTS present
curl -sI https://your-domain/login | grep -i content-security   # CSP present
curl -sI https://your-domain/login | grep -i cache-control      # no-store
curl -sI https://your-domain/manifest.webmanifest | grep -i content-type
#   → application/manifest+json, or the PWA will not install
```

Then, in a browser: register, fill in My World, name a desire, request a reading.
If it sits on "Reading your intentions" for more than a minute, the queue worker
is not running — check `/home/USER/logs/queue.log`.

---

## Known limitations, stated plainly

- **Registration confirms whether an email already has an account.** The login
  form is careful never to reveal membership; the signup form's uniqueness error
  undoes that. Fixing it properly needs an email-verification flow, which V1 does
  not have. Accepted, not overlooked.
- **No email verification and no password reset.** There is no account recovery.
  A forgotten password currently needs a manual reset at the shell.
- **Gratitude tags are stored in plaintext** (the `tag_index` column) so the
  archive can filter by them. Tag *labels* only — the entry bodies are encrypted.
- **Admin re-auth is a sliding two-hour window**, so an actively working admin is
  never forced to re-enter their password.
- **Quota counts a rolling 24 hours in UTC**, not the user's local day.
