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
| Deployed branch | `claude/erika-manifestation-demo-jgjpch` |
| Server | `2.25.93.114` (`srv1871005.hstgr.cloud`) |
| Coolify version | 4.3.8 |

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

**`REQUIRE_VERIFICATION` is off deliberately, and it is a stopgap.** There are
no `MAIL_*` variables on this application at all, so `MAIL_MAILER` falls back to
`log`. With verification on, nobody would ever receive a confirmation link and
every user would be permanently unable to generate anything.

The same gap means **password reset is silently broken in production today** —
`/forgot-password` reports success and sends nothing. That predates the beta
gates. Configure SMTP, then set `REQUIRE_VERIFICATION=true`.

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
