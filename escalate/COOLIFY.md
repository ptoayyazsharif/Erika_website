# Coolify — this instance

Where the live app is, how to deploy it from a terminal, and what to check.

[DEPLOY.md](DEPLOY.md) covers *configuring* Coolify — build pack, environment
variables, persistent storage, the Traefik label trap. This file is only the
connection details and the deploy command, kept separate because it is the part
that has now been lost twice to a wiped working copy and rediscovered by
guesswork both times.

**Nothing secret is in this file.** The API token is a credential and does not
belong in git — see [The token](#the-token).

---

## Connection details

| | |
|---|---|
| Panel | `http://coolify.2.25.93.114.sslip.io` |
| API base | `http://coolify.2.25.93.114.sslip.io/api/v1` |
| Application UUID | `um4fjdz4zww4aiv7xmj37czt` |
| **Live app** | **`https://escalate.cloud`** (and `www.`) |
| Old URL, still serving | `https://um4fjdz4zww4aiv7xmj37czt.2.25.93.114.sslip.io` |
| Deployed branch | `claude/escalate-app-bugs-l73sgw` (changed 25 Aug 2026 — see below) |
| Server | `2.25.93.114` (`srv1871005.hstgr.cloud`) |
| Coolify version | 4.3.8 |

### The deployed branch changed on 25 Aug 2026

It was `claude/erika-manifestation-demo-jgjpch`, and production silently fell
four commits behind: a redeploy rebuilt the same code, so a fix that was
committed, tested and pushed was reported as live and was not. Pushing across
branches is blocked from a Claude Code session, so Coolify was pointed at
`claude/escalate-app-bugs-l73sgw` instead.

Both branches still exist. To put it back, PATCH `git_branch` on the
application and redeploy — but then remember that work landing on the other
branch will not reach production on its own.

**Check what is actually deployed before believing a fix is live:**

```sh
curl -s "$COOLIFY_API/applications/$APP_UUID" -H "Authorization: Bearer $TOKEN" \
  | jq '{git_branch, status}'
```

### The panel is HTTP. The app is HTTPS.

`https://coolify.2.25.93.114.sslip.io` does not answer — only `http://` does.
Probing the `https://` form and concluding the panel is unreachable wastes an
hour. The app itself is the opposite: HTTPS, with HTTP redirecting to it.

### Domains

`fqdn` holds all three, comma separated:

```
https://escalate.cloud,https://www.escalate.cloud,https://um4fjdz4zww4aiv7xmj37czt.2.25.93.114.sslip.io
```

The sslip.io entry is kept on purpose. It is the address that cannot stop
working because of a DNS or certificate problem, which makes it the one to
check against when escalate.cloud misbehaves — if sslip is fine and the domain
is not, the fault is in DNS or the certificate, not in the app.

`APP_URL` is `https://escalate.cloud`. It must match, because `asset()` builds
absolute URLs from it and the CSP sends `upgrade-insecure-requests` on a secure
request — an `APP_URL` on the wrong scheme or host is how every stylesheet and
script on the page silently fails to load. See the note in SecurityHeaders.

Setting a domain through the API is a PATCH with a **`domains`** field, not
`fqdn`:

```sh
curl -sS -X PATCH -H "Authorization: Bearer $COOLIFY_TOKEN" \
  -H 'Content-Type: application/json' "$COOLIFY_API/applications/$APP_UUID" \
  -d '{"domains":"https://escalate.cloud,https://www.escalate.cloud"}'
```

`escalate.cloud` has an AAAA record as well as an A record. Both currently
answer, but if certificate renewal ever fails with nothing else changed, an
IPv6 address that no longer reaches this server is the first thing to check —
ACME will try it and will not fall back.

### `coolify.dot-kode.com` is dead

That hostname worked earlier and now has no DNS record at all:

```
getent hosts coolify.dot-kode.com   → no result
dot-kode.com                        → 31.97.44.211, answers 200
```

Not a proxy or policy problem — the record is simply gone. Don't chase it.

### `coolify.escaluxe.com` is not the panel either

Nor was it ever. It is a plausible-looking guess — the Coolify instance does host
`escaluxe.com` — and on 1 Sep 2026 a whole deploy was reported to the user as
blocked by the environment on the strength of it. The correct address is in the
table at the top of this file and always was.

What the failure looks like from a Claude Code session, so it is recognised
rather than re-diagnosed:

```
curl → 000
agent-proxy status → connect_rejected, "gateway answered 502 to CONNECT
                     (policy denial or upstream failure)"
```

That reads exactly like an egress policy denial and is not one. The tell is that
every other host answers — `escaluxe.com`, `escalate.cloud` and `api.github.com`
all returned 200 in the same minute. **When one host fails and the rest are
fine, suspect the hostname before the network, and read this file.**

## The token

A Coolify API token, shaped `2|…`. **Not stored in this repository and never to
be committed** — it can deploy, read every environment variable, and change
server configuration.

Ask for it, or mint one: Coolify → Keys & Tokens → API tokens.

```sh
export COOLIFY_TOKEN='…'
export COOLIFY_API='http://coolify.2.25.93.114.sslip.io/api/v1'
export APP_UUID='um4fjdz4zww4aiv7xmj37czt'
```

Tokens get revoked and reissued here fairly often. A `401` means the token is
stale, not that the URL is wrong.

---

## Deploying

### 1. Push first

Coolify builds from GitHub, never from a local working copy. An unpushed commit
cannot deploy — and the reverse trap is worse: **the live site can be correct
while the local checkout is stale**, which makes local greps lie to you.

```sh
git push -u origin claude/erika-manifestation-demo-jgjpch
```

### 2. Trigger it — POST, not GET

```sh
curl -sS -X POST -H "Authorization: Bearer $COOLIFY_TOKEN" \
  "$COOLIFY_API/deploy?uuid=$APP_UUID&force=false"
```

A GET returns `{"message":"This endpoint has changed to a POST request."}` — a
200 that deploys nothing. Older Coolify versions and older notes used GET.

The response carries a `deployment_uuid`.

### 3. Watch it finish

```sh
curl -sS -H "Authorization: Bearer $COOLIFY_TOKEN" \
  "$COOLIFY_API/deployments/<deployment_uuid>"
```

`status` runs `queued` → `in_progress` → `finished`. A build takes 1–2 minutes.

To find the UUID again, or check what branch is wired up:

```sh
curl -sS -H "Authorization: Bearer $COOLIFY_TOKEN" "$COOLIFY_API/applications"
```

### 3a. Setting environment variables

```sh
curl -sS -X POST -H "Authorization: Bearer $COOLIFY_TOKEN" \
  -H 'Content-Type: application/json' \
  "$COOLIFY_API/applications/$APP_UUID/envs" \
  -d '{"key":"INVITE_ONLY","value":"true","is_preview":false}'
```

**Do not send `is_build_time`.** This Coolify rejects it with
`422 {"errors":{"is_build_time":["This field is not allowed."]}}` — the column
is `is_buildtime` in the records the API returns, and the write endpoint does
not accept either spelling. Omit it; the default is what you want.

`is_preview` matters. `GET .../envs` returns **two rows for most keys**, and
they legitimately disagree:

| key | `is_preview:false` (production) | `is_preview:true` (preview builds) |
|---|---|---|
| `APP_URL` | `https://…` | `http://…` |
| `SESSION_SECURE_COOKIE` | `true` | `false` |

That is not a misconfiguration and does not need fixing — but a script that
reads the list without filtering on `is_preview` will report the wrong value for
production and send you chasing a cookie bug that is not there.

### 3b. Beta gates

Set on this instance as of c20d531:

```
INVITE_ONLY=true            # registration needs a code from escalate:invite
REQUIRE_VERIFICATION=false  # see below
INVITE_DAYS=30
CEILING_STORIES_PER_DAY=200
CEILING_NARRATIONS_PER_DAY=300
CEILING_REWINDS_PER_DAY=100
```

**`REQUIRE_VERIFICATION` is off, and it is now the only thing holding it off.**
It was a stopgap for a real gap: mail fell back to `log`, so with verification
on nobody would have received a confirmation link and every user would have
been permanently unable to generate anything. The same gap meant password reset
reported success and sent nothing.

**That gap is closed.** Resend is wired up through the admin panel and mail is
confirmed delivering — a test from `hello@escalate.cloud` landed in a Gmail
inbox, not spam, on 25 Aug 2026. Password reset works from that moment on, and
`REQUIRE_VERIFICATION=true` is now safe to set.

Mail is configured from **Admin → Settings → Mail**, not from `MAIL_*`
variables — see [Mail](#mail).

### 3c. Minting invites needs a shell, not the API

`escalate:invite` runs inside the container. Coolify's
`POST /applications/{uuid}/execute` exists, but a Claude Code session may be
blocked from calling it by the permission classifier — arbitrary remote command
execution on a production host is exactly what that guard is for. Use the
**Terminal** tab in the Coolify panel, or ssh to `2.25.93.114` and
`docker exec` into the container:

```sh
php artisan escalate:invite --count=10 --note="beta round one"
php artisan escalate:invites --open
```

### 4. Verify what is actually served

Section 7 of [DEPLOY.md](DEPLOY.md) has the full list. The short version: curl
the built assets and grep for something only the new build contains.

```sh
L=https://um4fjdz4zww4aiv7xmj37czt.2.25.93.114.sslip.io
curl -s "$L/sw.js"       | grep -o 'escalate-v[0-9]'
curl -s "$L/js/app.js"   | grep -c 'controllerchange'
curl -s "$L/css/app.css" | grep -c 'field-grid'
```

---

## What curl cannot tell you

This is the one that has burned us twice, both times reported as fixed on the
strength of a green curl check:

- **The service worker.** curl never registers one. A worker that matched
  cached assets with the query string ignored served every real user a
  months-old `app.js` for weeks, while curl saw the correct file every time.
  Buttons "did not work" because the code behind them was not in the build
  those browsers were running. See the comments in `public/sw.js`.
- **CSP.** `upgrade-insecure-requests` on a plain-HTTP origin broke every asset
  on the page, and curl returned 200 for all of them, because only a browser
  applies CSP.

Anything that lives in the browser gets checked in a browser: `tests/browser/`.

```sh
php artisan serve --port=8123 &
node tests/browser/journey.mjs          # the buttons, clicked
node tests/browser/service-worker.mjs   # a deploy actually reaching a client
```

### These cannot be pointed at production from a Claude Code session

Chromium in that sandbox cannot reach external hosts at all — directly or
through the egress proxy, HTTP or HTTPS, and it is not a certificate problem
(pinning the proxy CA's SPKI changes nothing; the connection is reset).

So a browser run proves the code, against a local server running that same
commit. Pair it with the curl checks above to prove production is serving that
commit. Say it that way — do not imply the live site was driven in a browser.

## Mail

There are two halves and they do different jobs. Keep them straight.

### Sending: Resend, from the admin panel

**Live and confirmed** as of 25 Aug 2026: Resend, sending as
`hello@escalate.cloud`, proven by a test email that reached a Gmail inbox
rather than the spam folder.

Configured in **Admin → Settings → Mail**; *Send a test email* really sends, so
a silence there is a real failure and not a config guess. Nothing is stored in
`MAIL_*` on the Coolify application — the settings live in the `settings` table
and `Settings::apply()` overlays them onto `config('mail')` at boot.

Sending from the mailbox below instead would be a mistake. VPS IPs have no
sending reputation, `2.25.93.114` has no SPF or DKIM behind it, and a password
reset that lands in spam is a password reset that did not happen.

### Receiving: `escalate-mail`, a Coolify service

Poste.io, service uuid `nn5prfsrcz7k8qsd8bhbphgt`, in project ESCALATE /
production. One container carrying SMTP, IMAP, Roundcube webmail and an admin
UI. It exists so the domain owns a real mailbox — somewhere to sign up to
services from, and to keep what arrives, without renting that from anyone.

Admin UI: <http://mail-nn5prfsrcz7k8qsd8bhbphgt.2.25.93.114.sslip.io>
First visit lands on `/admin/install/server`, which creates the first mailbox.

Receiving is the easy half of mail. No reputation is involved, and the outbound
port-25 blocks that stop VPS hosts sending do not affect what arrives.

**DNS is already right for receiving.** `escalate.cloud` A → `2.25.93.114`, and
MX → `10 escalate.cloud.`, so other mail servers already deliver to this host.
Nothing was listening before; now something is.

Two things are still open, and both need a human:

- **`mail.escalate.cloud` does not resolve** (NXDOMAIN). Add an A record to
  `2.25.93.114` at Hostinger, then set the domain on the service in the Coolify
  UI — the API has no field for a sub-service's FQDN, only `name`,
  `description`, `connect_to_docker_network` and `docker_compose_raw`, and
  re-parsing the compose does not overwrite an FQDN that was already assigned.
  The `SERVICE_FQDN_MAIL*` env vars on the service are already set to the
  intended hostname. Until then the sslip.io URL above is the way in, and it
  works today.
- **Inbound port 25 is unverified.** The Claude Code sandbox egresses on 80 and
  443 only, so it cannot open an SMTP connection to prove it. From a laptop:
  `nc -vz escalate.cloud 25`, or just send the new mailbox a message.

### The ports are published to the host, not routed by Traefik

`25`, `143`, `993`, `587`, `465`. Mail protocols are not HTTP; Traefik has
nothing to do with them. Only the web UI goes through Traefik, which is why the
container runs with `HTTPS=OFF` — it would otherwise fight Traefik for a
certificate.

Mail lives in the named volume `nn5prfsrcz7k8qsd8bhbphgt_mail-data`. That is
the whole point of the service, so do not `docker volume rm` it while cleaning
up, and do not swap it for a bind mount without moving the data first.

### One trap in the plan

If the Resend account's recovery address is a mailbox on this box, then a VPS
outage takes out both the mailbox and the means to recover the service you
would need in order to fix it. Put a second recovery method on the Resend
account — a phone number, or an address somewhere else.
