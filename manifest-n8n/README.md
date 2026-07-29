# Manifest — n8n backend + HTML frontend

Two files. The whole app.

```
manifest-workflow.json   → import into n8n. This is the entire backend.
index.html               → the app. Open it anywhere. No build step.
```

The workflow exposes two webhooks; the HTML calls them. Nothing else to host,
no database, no server code to deploy.

---

## 1. Import the workflow

n8n → **Workflows** → **Import from File** → pick `manifest-workflow.json`.

You'll get nine nodes in two chains:

```
POST /manifest/story  →  Build Prompt  →  Anthropic  →  Tidy Story  →  Return Story
POST /manifest/voice  →  Resolve Voice →  ElevenLabs →  Return Audio
```

## 2. Attach the two credentials

**Anthropic** — open the **Anthropic — Write Story** node. Under *Credential for
Anthropic API*, pick the credential you already created. Nothing else to change.

**ElevenLabs** — open the **ElevenLabs — Narrate** node. It uses **Header Auth**
(not the community ElevenLabs node, which has known 401 issues). If you don't
have a Header Auth credential yet, create one:

| Field | Value |
|---|---|
| Name | `xi-api-key` |
| Value | your ElevenLabs API key |

Then select it in the node.

## 3. Add your voice IDs

Open the **Resolve Voice** node. At the top:

```js
const VOICES = {
  calm:      'EXAVITQu4vr4xnSDxMaL',
  warm:      'XrExE9yKIg1WjnnlVkGX',
  confident: 'pFZP5JQG7iQjIQuC4Bku',
};
```

Those are stock voices. Replace them with IDs you've actually auditioned from
<https://elevenlabs.io/app/voice-library> — the voice is half the product.

## 4. Activate, then copy the two URLs

Toggle the workflow **Active** (top right). Click each Webhook node and copy its
**Production URL**. They'll look like:

```
https://your-n8n.app/webhook/manifest/story
https://your-n8n.app/webhook/manifest/voice
```

## 5. Point the frontend at them

Open `index.html`, find the CONFIG block near the top (~line 358):

```js
const API = {
  story: 'https://YOUR-N8N-HOST/webhook/manifest/story',
  voice: 'https://YOUR-N8N-HOST/webhook/manifest/voice',
};
const VOICE = 'calm';           // calm | warm | confident
```

Paste your two URLs. That's the entire setup — open the file and it works.

Host it anywhere static: the existing GoDaddy folder, Netlify, Vercel, S3, or
just double-click it locally.

---

## How it flows

1. Fifteen questions collect the profile (autosaved to `localStorage`).
2. `POST /manifest/story` → Claude writes a 400–550 word present-tense reading.
   Returns `{ story, lines, words, model }` — `lines` is pre-split for the reveal.
3. `POST /manifest/voice` → ElevenLabs Flash v2.5 narrates it. Returns raw mp3.
4. The reveal lights each line in proportion to its word count across the
   voice's real duration, so text tracks the narration instead of a fixed timer.

## Cost, and the cache that controls it

Roughly **17¢ per fresh reading** — about 1¢ Claude, about 16¢ ElevenLabs.

The frontend stores every generated mp3 in **IndexedDB**, keyed by
`sha256(text + voice)`. A reading already generated on a device never bills
again, no matter how many times it's replayed or how many reloads happen in
between. Verified: a second run of the same reading makes **zero** requests to
the voice webhook.

That's per-device. To dedupe across users, add an S3/R2 upload after the
ElevenLabs node and check for the object before calling out — the hash key is
already the right shape for it.

## Changing the model

In **Anthropic — Write Story**, edit the JSON body:

| Want | Set `model` to | ~cost per reading |
|---|---|---|
| Best prose | `claude-opus-5` | ~2¢ |
| **Recommended** | `claude-sonnet-5` | ~1.2¢ |
| Cheapest | `claude-haiku-4-5` | ~0.4¢ |

The story prompt itself lives in the **Build Prompt** node. The contrast beat
("Three years ago, I would have…") is the emotional centre of the reading and
the reason question 15 exists — don't cut it.

## What's deliberately not here

- **No accounts, payments, or user storage.** The paywall is a static mock.
- **No server-side audio storage.** Caching is per-device (see above).
- **No rate limiting.** n8n webhooks are public once active — anyone with the
  URL can spend your API credits. Before this faces real traffic, put it behind
  auth: add an **IF** node after each webhook checking a shared secret header,
  or front it with Cloudflare Access.

## Troubleshooting

| Symptom | Cause |
|---|---|
| "Could not reach the reading service" | URLs still say `YOUR-N8N-HOST`, or the workflow isn't Active. Test URLs only work while the canvas is open with *Listen for Test Event* running. |
| CORS error in the browser console | The Webhook node's **Allowed Origins (CORS)** is set to `*` in this template — check it survived import. |
| 401 from ElevenLabs | Header Auth name must be exactly `xi-api-key`, lowercase. |
| Audio plays but no text appears | The story chain returned no `lines`. Open the execution log on the **Tidy Story** node. |
| Voice never starts on iPhone | Safari blocks autoplay; the app catches this and shows "Tap anywhere to start the voice." |
