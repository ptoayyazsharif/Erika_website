# Deploying Escalate with Coolify

The whole app ships as one Docker image: nginx, php-fpm and the queue worker in
a single container. Coolify builds it from `escalate/Dockerfile`, runs it, and
puts its own proxy and TLS in front.

Everything below has been run. The image builds, boots, migrates, serves and
drains its queue — the checks in section 7 are the ones actually performed
against it, not a wishlist.


> **Panel URL, application UUID and the one-line deploy command** live in
> [COOLIFY.md](COOLIFY.md), along with what a curl check cannot prove.
> This file is how the application is configured.

---

## 0. Why not Nixpacks

The first deploy used Coolify's default build pack and died here:

```
#9 RUN sudo apt-get update && sudo apt-get install -y --no-install-recommends curl wget
Get:12 http://security.ubuntu.com/ubuntu noble-security/main amd64 Packages [1110 kB]
   … two minutes of nothing …
DeploymentException, exit code 255
```

That was a network stall fetching Ubuntu package lists — nothing to do with the
app. Re-running it would have been a coin flip, and Nixpacks was getting four
other things wrong that would have surfaced later as "the app is broken":

| Nixpacks did | Consequence |
|---|---|
| Ignored `NIXPACKS_PHP_VERSION=8.4`, installed php83 | Works, but not what was asked for or tested |
| `composer install --ignore-platform-reqs` | Missing extensions become runtime fatals instead of build errors |
| Never ran `php artisan migrate` | Every page 500s on a missing table |
| Never set `APP_KEY` | Nothing encrypts; the app cannot store a journal entry |
| Started no queue worker | Every reading sits on "Reading your intentions" forever |

The Dockerfile does all five correctly and has no apt layer at all — Alpine's
repositories are the only OS packages it touches.

---

## 1. Coolify settings

Application → Configuration → **General**:

| Field | Value |
|---|---|
| Build Pack | **Dockerfile** |
| Base Directory | `/escalate` |
| Dockerfile Location | `/escalate/Dockerfile` |
| Ports Exposes | **8080** |
| Publish Directory | *(leave empty — Dockerfile builds ignore it)* |

Base Directory is what makes the build context `escalate/`, so `COPY . .` picks
up the app rather than the repository root.

**Ports Exposes must be 8080.** Coolify defaults to 3000 and the container
listens on 8080; a mismatch shows up as a container that is healthy but that the
proxy reports as unreachable. Nothing binds a privileged port, so the container
never needs extra capabilities.

> **If you change the port through the API rather than the UI, change the
> labels too.** Coolify caches the generated Traefik/Caddy labels on the
> application and does *not* regenerate them when `ports_exposes` changes via
> `PATCH /applications/{uuid}`. The result is a container reporting `healthy`,
> a Coolify status of `running:healthy`, and every request returning **502**,
> because the proxy is still dialling the old port. It cost a deploy to find.
> The labels live base64-encoded in `custom_labels`; decode, replace
> `loadbalancer.server.port=3000` and `{{upstreams 3000}}` with 8080, PATCH it
> back, and redeploy. Setting the port in the UI regenerates them for you.

Configuration → **Health Checks**: path `/up`, port `8080`. That is Laravel's
own health route, and the image carries a `HEALTHCHECK` for it too, so a broken
deploy is visible in `docker ps` even without Coolify.

### Persistent storage — do this before the first deploy

Storage → Add → **Volume Mount**

| Field | Value |
|---|---|
| Name | `escalate-storage` |
| Destination Path | `/var/www/html/storage` |

Everything a user ever creates lives under that one path: the SQLite database,
narration audio, vision images, sessions, the queue. Without the volume every
redeploy silently starts a brand new empty app — the deploy looks perfectly
successful and every account is gone.

The container recreates the directory skeleton inside the volume on each boot,
so an empty volume on the first run is expected and fine.

---

## 2. Environment variables

Coolify → Environment Variables. **Leave "Build Variable?" unchecked on every
one of these.** A build variable is baked into the image, which for
`ANTHROPIC_API_KEY` means the key is readable by anyone who can pull the image
or run `docker history`. These are all runtime values.

```dotenv
APP_NAME=Escalate
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:…                 # see the warning below
APP_URL=https://your-domain      # https, no trailing slash

LOG_CHANNEL=stderr               # so Coolify's log view shows them
LOG_LEVEL=error

DB_CONNECTION=sqlite             # the file is created inside the volume

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict

# Coolify's proxy reaches the container over the Docker network. Naming the
# private ranges rather than '*' is the point: with '*' anyone can forge an
# X-Forwarded-For header, and every rate limit in this app — including the
# login throttle — is keyed on the client IP.
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16

# Must stay above NarrateStory's 240s timeout, or the database queue hands a
# still-running job to a second worker and the narration is paid for twice.
DB_QUEUE_RETRY_AFTER=300

ANTHROPIC_API_KEY=sk-ant-…
ELEVENLABS_API_KEY=sk_…
```

`.env.example` lists the rest — model names, quotas, timeouts. The defaults in
`config/escalate.php` are sensible; set them only to change them.

### APP_KEY

Generate it once, on your own machine:

```bash
cd escalate && php artisan key:generate --show
```

Paste the whole `base64:…` string into Coolify and **never change it again.**

Every private field in this app is encrypted at rest with that key: journal
entries, gratitude, faith language, failure reasons. Replace the key and all of
it becomes permanently unreadable — no recovery, no support ticket, no export.

The container will not start without one, and it deliberately does *not*
generate its own. A container that generated a key on boot would work
beautifully until its first restart, and then quietly orphan everything every
user had written.

---

## 3. First deploy

1. Set everything in sections 1 and 2, **volume included**.
2. Deploy.
3. Watch the logs. A healthy first boot reads:

```
[escalate] creating a new SQLite database at /var/www/html/storage/database/escalate.sqlite
[escalate] running migrations
   INFO  Running migrations.
[escalate] warming caches
[escalate] ready — nginx on :8080, php-fpm, and one queue worker
INFO success: php-fpm entered RUNNING state
INFO success: nginx entered RUNNING state
INFO success: queue entered RUNNING state
```

All three must reach RUNNING. If the queue one is missing, generation never
finishes and it looks exactly like the AI being broken.

If the container exits immediately, the last line before it says why in plain
English — a missing `APP_KEY`, a truncated one, and `APP_DEBUG=true` in
production all stop the boot on purpose.

---

## 4. Making an admin

There is no web path to admin privilege, by design. Register normally through
the site, then from Coolify → Terminal (or `docker exec` on the host):

```bash
php artisan escalate:make-admin you@example.com
```

Admin is a *second* door: sign in as usual, then confirm your password again at
`/admin/login`. To everyone else both `/admin` and `/admin/login` return 404 —
the area is invisible, not merely forbidden.

---

## 4a. Running a closed beta

Three gates stand between a fresh deploy and an unexpected provider invoice.
All three default to on; none of them is a paywall, and none of them touches
billing.

**Invite-only registration.** `INVITE_ONLY=true` (the default) means `/register`
needs an unclaimed code. Mint them from the container:

```bash
php artisan escalate:invite --count=10 --note="first testers"
php artisan escalate:invite --email=maya@example.com --days=7   # bound to one address
php artisan escalate:invites          # who has been invited, and who came in
php artisan escalate:invites --open   # what is left to hand out
```

Each code prints with a signup link that prefills it, so the invitation is one
tap. A code is single-use, enforced by a conditional UPDATE rather than a
read-then-write — two people pasting the same code from a group chat is the race
that matters, and the database decides it. A failed signup (mistyped password,
say) does not spend the code.

**Email verification.** `REQUIRE_VERIFICATION=true` (the default) means the four
routes that call a paid provider — write a reading, narrate one, rewrite one,
write a Rewind — need a confirmed address. Nothing else does: an unconfirmed
account can still sign in, fill in My World and name desires. A spam filter
should not be able to take somebody's journal away from them.

> **This needs working SMTP.** So does password reset, so it is not a new
> requirement — but if `MAIL_MAILER` is still `log`, beta users will never
> receive the link and generation stays shut for all of them. Configure mail, or
> set `REQUIRE_VERIFICATION=false` deliberately while you sort it out.

**The ceiling.** A whole-application daily cap, on top of the per-user quotas:

```
CEILING_STORIES_PER_DAY=200
CEILING_NARRATIONS_PER_DAY=300
CEILING_REWINDS_PER_DAY=100
```

The per-user quota is an allowance and it multiplies by the number of accounts;
this one does not. Size it at roughly (expected users) × (per-user quota) with
headroom — the defaults suit twenty to thirty people. `0` means unlimited, not
blocked, so an unset variable fails open rather than bricking the app.

When the ceiling is reached everyone is told the same thing, and it is worded so
nobody reads it as their own limit: *"Escalate has reached its limit for today
across everyone using it."* Watch for it with:

```bash
php artisan tinker --execute="echo App\Support\Ceiling::remaining('story');"
```

### Opening up later

Turning both gates off is two variables, and the combination is worth being
deliberate about:

```
INVITE_ONLY=false
REQUIRE_VERIFICATION=false   # <- do not set this one without a reason
```

`INVITE_ONLY=false` with verification still on is the normal post-beta state.
Both false is an open door to your provider bill, guarded only by the ceiling.

## 4b. Turning on billing

`BILLING_ENABLED=false` is the default and it is doing real work: with it off,
`Quota::limit()` returns the flat `escalate.quotas` numbers and every user gets
what they got before Stripe existed. **Shipping the billing code changes nothing
for anyone until somebody enables it on purpose.** Leave it off through the
beta.

To switch it on:

1. **Stripe keys.** `STRIPE_KEY`, `STRIPE_SECRET` from the dashboard.
2. **Prices.** Create the products in Stripe, then set `STRIPE_PRICE_MONTHLY`
   and `STRIPE_PRICE_YEARLY` to the `price_…` ids. A plan with an empty price id
   is hidden from the picker rather than offered as a button that 500s — so if
   a plan does not appear, that is why.
3. **The webhook.** Add an endpoint in Stripe pointing at
   `https://<host>/stripe/webhook`, and set `STRIPE_WEBHOOK_SECRET` to its
   signing secret. Subscribe it to at least `customer.subscription.created`,
   `customer.subscription.updated`, `customer.subscription.deleted` and
   `invoice.payment_succeeded`.
4. `BILLING_ENABLED=true`, redeploy.

**The webhook is not optional.** This app reads entitlement from its own
`subscriptions` table, never by calling Stripe on the request path — a
generation must not depend on a third party being reachable. That table is
written by the webhook. Without it, people pay and nothing changes in the app.

**`STRIPE_WEBHOOK_SECRET` does not behave the way the name suggests, and this
is worth knowing before you trust it.** Cashier applies its signature check
*conditionally*:

```php
if (config('cashier.webhook.secret')) {
    $this->middleware(VerifyWebhookSignature::class);
}
```

An unset secret therefore does not disable the endpoint — it disables the
*authentication on* the endpoint and leaves it processing whatever anyone
posts. Since those handlers write the `subscriptions` table this app reads
entitlement from, an unauthenticated caller could grant themselves a plan.

`App\Http\Middleware\RequireStripeWebhookSecret` closes that: with no secret
configured, the endpoint returns 403 instead of accepting the event. So the
setting now means what an operator would assume it means. If subscriptions are
not appearing, check Stripe's delivery log — a 403 there means the secret is
missing or wrong, not that the endpoint is absent.

### What is deliberately not built

Cards, invoices, tax, dunning and cancellation are Stripe's screens, reached
via Checkout and the Billing Portal. This app never renders a card field, which
keeps it in PCI DSS SAQ A rather than SAQ A-EP. Do not add one.

Cancelling lives in the portal for the same reason: a cancel button here would
have to reproduce Stripe's proration and grace-period rules to tell the truth
about what happens next, and one that misstates a refund is worse than an extra
click.

### Before you charge anybody

`docs/LEGAL-PACK.md` flags this and it is not boilerplate: **US auto-renewal
disclosure rules are strict and several states enforce them aggressively.** The
plan page carries a plain renewal sentence, which is a floor, not compliance.
Terms covering payment, refunds and auto-renewal are a lawyer's job before the
first live charge.

### The image needs bcmath

`laravel/cashier` requires `ext-bcmath`, and it is installed in **both** Docker
stages. That pairing is deliberate — stage 1 resolves `ext-*` against its own
PHP, so an extension missing there fails the build honestly instead of
producing a vendor tree the runtime cannot load. If you ever find yourself
reaching for `--ignore-platform-reqs`, the extension list is what actually needs
fixing.

## 4c. The admin panel

`/admin`, behind the role **and** the second password door (`/admin/login`,
two-hour idle expiry). A failed check 404s, so the area is invisible to anyone
without the role.

| Screen | What it does |
|---|---|
| Overview | Accounts, invites, today's spend against the ceiling, recent provider failures |
| People | Search accounts, see counts and usage, comp someone onto a plan, suspend or restore |
| Invites | Mint, list and withdraw invite codes without a terminal |
| Settings | API keys, Stripe keys and price ids, the beta gates, every quota and ceiling |

### Settings override the environment, and can be handed back

Values live in a `settings` table and are laid over the config files once per
request at boot, so everything downstream keeps reading `config()` and needs to
know nothing about it. A key with no row falls through to `config/escalate.php`
and therefore to the environment.

That ordering is what makes "Reset" meaningful: clearing a field deletes the row
and returns the app to what it was deployed with. When something is wrong and
you are not sure what, reset is the safe move.

**Three properties are load-bearing, and each has a test:**

- **Only allowlisted keys can be written.** The list is in
  `App\Support\Settings::editable()`. Without it, "save these settings" is an
  arbitrary config write and `app.key` is one crafted field name away.
- **Secrets go in and never come back.** A saved API key renders as `••••1234`.
  An admin can rotate a key without ever being shown one — and a blank secret
  box means "leave it alone", not "delete it", or saving the page to change a
  quota would wipe the key.
- **A database problem cannot stop the app booting.** `Settings::apply()` runs
  in `AppServiceProvider::boot()` and reads through the cache, which is the
  `database` store in production. Unguarded, that turns any database fault into
  a total boot failure — including `artisan migrate`, which the entrypoint runs
  before those tables exist on a fresh volume. It is wrapped so a failure falls
  back to no overrides and the app comes up with what it was deployed with.

### Plans

Admin → **Plans**. Create, edit, reorder, deactivate. Free is protected: it
cannot be renamed, deactivated or deleted, because it is what everyone is on
before they pay and what a lapsed subscription falls back to.

A plan with people on it cannot be deleted either — deleting it would leave
their subscriptions pointing at a price this app no longer recognises while
Stripe carries on charging them. **Deactivate instead**: it disappears from the
picker and keeps working for everyone already on it.

Plans were in `config/escalate.php` and are now a table, seeded from that config
on migrate. Config remains the fallback for an install whose table is empty.

### Pricing a plan

Set the **amount** on the plan in the admin panel. The Stripe Product and Price
are created for you, in whichever mode you are in, using that mode's keys. You
do not open the Stripe dashboard to sell something.

This needs **Products (write)** and **Prices (write)** on the API key — more
than a read-only key. Nothing is ever deleted in Stripe; old prices are
archived.

**A Stripe Price cannot be edited.** Changing an amount therefore creates a new
price and archives the old one, and that is the behaviour you want: everyone
already subscribed keeps paying what they agreed to until you move them
deliberately. Raising a price does not silently raise it for existing
customers — which for auto-renewal is very often the legally required
behaviour, not merely the decent one.

The plan is saved locally **before** Stripe is called. If the key is wrong or
Stripe is unreachable, the typing is safe and the message says what did not
happen over there; saving again once it is fixed picks up where it left off.

### Stripe test mode

Admin → Settings → **Use test mode**, with separate test and live key sets.

**Each plan carries two price ids, and that is not redundancy.** Stripe keeps
test and live as entirely separate worlds — separate keys, separate customers,
separate webhook secrets, and separate price ids. `price_abc` created in test
does not exist in live. One column would mean flipping the switch silently
pointed checkout at ids the active keys cannot resolve, and the failure would
land in front of a customer rather than in a deploy.

So the mode selects the whole set: keys, webhook secret, and which price id each
plan uses. A plan priced only for live is hidden from the picker in test mode
rather than offered as a button that errors.

`App\Support\Stripe` is the one place that resolves this, and
`Settings::apply()` copies the active set into `cashier.*` so Cashier needs no
knowledge of any of it. **The webhook secret is per-mode too** — a test-mode
endpoint has its own, and pointing Stripe's test webhooks at the live secret
gives 403s that look like the endpoint is broken.

### Comping someone

People → a person → Plan. It sets `users.plan_override`, which `Plan::for()`
checks before Stripe. It deliberately does **not** create a subscription row: a
row that looks like a payment nobody made is the kind of thing that cannot be
explained when it is reconciled against Stripe later. Removing the override
returns them to whatever they are actually subscribed to.

### What the admin area cannot do

It cannot read anyone's journal. Every desire, reading, gratitude entry and
rewind stays encrypted, and the people screens show counts only. That is not
squeamishness — `/privacy` tells users their entries are not readable by staff,
and a screen that rendered them would make that false. There is a test asserting
no journal content appears on either people screen; keep it.

Making an admin is still `php artisan escalate:make-admin <email>`. There is no
web route that grants the role, on purpose: privilege escalation should need a
shell, not a form someone might find.

## 4d. Mail

Configured in **Admin → Settings → Mail**, with a **Send a test email** button
that sends one real message to the administrator pressing it. Nothing else is a
real test: the only proof of mail is mail that arrives.

The button refuses while the mailer is `log`, because that is the state where
"it worked" is most misleading — the log driver succeeds at everything and
delivers nothing. That is exactly what this deployment did for a long time, and
it is why `/forgot-password` reported success and sent nothing.

### Do not self-host outbound SMTP for this

It is tempting — there is a server, Coolify runs services, and it feels tidier
than another account. It is the wrong tool here, for reasons that have nothing
to do with effort:

- **Port 25 outbound is blocked by most VPS providers**, which is the port other
  mail servers accept on. That alone usually ends it.
- **A fresh IP has no sending reputation.** Gmail and Outlook treat unknown
  senders as suspicious by default, and this is transactional mail — a password
  reset that lands in spam is an account somebody cannot get back into.
- **SPF, DKIM, DMARC and a matching PTR record** are all required before the
  large providers will even consider you, and a PTR needs the host to set it.
- A shared IP that ever sends something spammy is one that stops delivering for
  everything, including the reset links.

Use a transactional provider. Resend, Postmark, Brevo and Mailgun all have free
tiers that cover a beta comfortably, and every one of them is SMTP credentials
pasted into the settings screen — the same one-time paste as anything else, not
an ongoing back-and-forth.

Whichever you pick: **verify the sending domain with them** (they will give you
DNS records for `escalate.cloud`), and set the From address to something on that
domain. An unverified From is rejected or filed as spam no matter how correct
the SMTP settings are.

### Seeing mail without sending it

If you want to watch what the app produces without delivering anything —
useful while testing the verification and reset screens — Coolify can run
**Mailpit** as a service: a fake SMTP server with a web inbox. Point the mail
host at it and every message the app sends appears in a browser. It delivers to
nobody, so it is for testing the flow, never for real users.

## 5. Backups

Back up **the volume**. `escalate-storage` holds the database and every audio
file and photo; nothing of value lives anywhere else.

Store `APP_KEY` somewhere else entirely — a password manager, not the same
archive. A backup that contains both the database and the key is the plaintext
journal of every user, in one file. Kept apart, a stolen backup is noise.

Coolify can schedule volume backups under Storages. Restore is the reverse:
mount the volume, set the same `APP_KEY`, deploy.

---

## 6. Updating

Push to `claude/erika-manifestation-demo-jgjpch` and redeploy. The container
runs `php artisan migrate --force` before it accepts traffic, so schema changes
apply themselves; if a migration fails the container refuses to start rather
than serving half a schema.

The old container is replaced rather than reused, so anything OPcache held goes
with it. Nothing needs clearing by hand.

---

## 7. Verify after deploying

These are the checks run against the built image. Repeat them against your own
domain.

```bash
curl -sI https://your-domain/login | grep -i content-security   # CSP present
curl -sI https://your-domain/login | grep -i strict-transport   # HSTS present
curl -sI https://your-domain/login | grep -i cache-control      # no-store
curl -sI https://your-domain/manifest.webmanifest | grep -i content-type
#   → application/manifest+json, or the PWA will not install

curl -so /dev/null -w '%{http_code}\n' https://your-domain/.env         # 403/404
curl -so /dev/null -w '%{http_code}\n' https://your-domain/foo.php      # 404
curl -so /dev/null -w '%{http_code}\n' https://your-domain/storage/app/ # 404
```

HSTS only appears when the request arrives over HTTPS *and* `TRUSTED_PROXIES`
is set. If it is missing here that variable is wrong — and so is every IP-keyed
rate limit in the app.

Then in a browser: register, fill in My World, name a desire, ask for a
reading. It should move off "Reading your intentions" within about half a
minute. If it never does, the queue worker is not running — check the logs for
`success: queue entered RUNNING state`.

---

## 8. What is in the container

| Process | Runs as | Why |
|---|---|---|
| supervisord | root | PID 1; restarts anything that dies |
| nginx master | root | Only so it can open `/dev/stderr`; workers are www-data |
| php-fpm master | root | The pool drops to www-data, as the official image intends |
| queue worker | www-data | `queue:work --tries=2 --timeout=240 --max-time=3600` |

The worker retires hourly by `--max-time` and supervisor restarts it, so
nothing accumulates. `pcntl` is compiled in specifically so `SIGTERM` lets a
worker finish the job in hand — a redeploy mid-narration should not leave a
story stuck in `rendering`.

`--no-dev` at build time is a security control, not a size optimisation:
`filp/whoops` is a dev dependency and its error page dumps `$_ENV`, which is
both API keys and `APP_KEY`. With it absent, even an accidental
`APP_DEBUG=true` could not render them — and the entrypoint refuses to boot
with that combination anyway.

One line in `docker/fpm-pool.conf` deserves naming, because its absence is
silent and catastrophic: `clear_env = no`. php-fpm wipes the worker's
environment by default, so without it `APP_KEY` and both API keys read as null.
The app would boot, serve the login page, and fail only when something tried to
decrypt.

---

## Appendix: deploying without Docker

If this ever runs on plain shared hosting instead, one thing matters more than
everything else combined:

**Point the document root at `escalate/public`.** Not the repository root, not
`escalate/`. Apache does not block `.env` by default — only `.ht*` files — so a
document root one level too high serves, as plain downloadable files:

| URL | What it hands over |
|---|---|
| `/escalate/.env` | Both API keys **and `APP_KEY`** |
| `/escalate/database/*.sqlite` | The whole database |
| `/escalate/storage/logs/laravel.log` | Every stack trace |
| `/escalate/storage/app/escalate/…` | Every user's narration, ownership check bypassed |

That single misconfiguration defeats the encryption entirely: `APP_KEY` and the
database downloaded from the same tree is plaintext for every journal in the
system. `escalate/.htaccess` denies everything as a second layer — but it only
helps if `mod_authz_core` behaves, so verify with the curls in section 7 rather
than assuming.

You would also need, by hand, everything the container does for you: a queue
worker on cron, `php artisan migrate --force`, `config:cache`, `chmod 600 .env`,
and `composer install --no-dev`.

---

## Known limitations, stated plainly

- **Registration confirms whether an email already has an account.** The login
  form is careful never to reveal membership; the signup form's uniqueness error
  undoes that. Fixing it properly needs an email-verification flow, which V1 does
  not have. Accepted, not overlooked.
- **No email verification.** Anyone can register with an address they do not
  own. Password reset exists and works, so account recovery is fine.
- **Password reset needs a real mailer.** With `MAIL_MAILER=log` the reset link
  goes to the log rather than to the person. Set real SMTP credentials before
  telling anyone the feature exists.
- **Gratitude tags are stored in plaintext** (the `tag_index` column) so the
  archive can filter by them. Tag *labels* only — the entry bodies are encrypted.
- **Admin re-auth is a sliding two-hour window**, so an actively working admin is
  never forced to re-enter their password.
- **Quota counts a rolling 24 hours in UTC**, not the user's local day.
- **One queue worker.** Two people asking for a reading at the same moment are
  served one after the other. At this scale that is the right trade; if it stops
  being one, raise `numprocs` in `docker/supervisord.conf`.
