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
| Live app | `https://um4fjdz4zww4aiv7xmj37czt.2.25.93.114.sslip.io` |
| Deployed branch | `claude/erika-manifestation-demo-jgjpch` |
| Server | `2.25.93.114` (`srv1871005.hstgr.cloud`) |
| Coolify version | 4.3.8 |

### The panel is HTTP. The app is HTTPS.

`https://coolify.2.25.93.114.sslip.io` does not answer — only `http://` does.
Probing the `https://` form and concluding the panel is unreachable wastes an
hour. The app itself is the opposite: HTTPS, with HTTP redirecting to it.

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
