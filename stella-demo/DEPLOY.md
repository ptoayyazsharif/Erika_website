# Deploying to GoDaddy cPanel

Twenty minutes, start to finish. No Composer, no SSH required.

## 1. Set the PHP version

cPanel → **MultiPHP Manager** → select the domain → set to **PHP 8.1 or newer**.
`api/status.php` will tell you what it actually got.

## 2. Upload

Zip the `stella-demo` folder, then cPanel → **File Manager** → open
`public_html` → **Upload** → select the zip → right-click it → **Extract**.

You should end up with `public_html/stella-demo/index.html`.

## 3. Paste your keys

Edit `api/config.php` in File Manager (right-click → Edit):

```php
define('LLM_PROVIDER',      'auto');       // uses whichever key you fill in
define('ANTHROPIC_API_KEY', 'sk-ant-…');   // or:
define('OPENAI_API_KEY',    'sk-…');
define('ELEVENLABS_API_KEY', '…');
```

Then the voices. Pick two or three from
<https://elevenlabs.io/app/voice-library>, audition them reading a sample
sentence first — the voice is half the product — and paste the IDs:

```php
$VOICES = [
  'calm'      => 'EXAVITQu4vr4xnSDxMaL',
  'warm'      => 'XrExE9yKIg1WjnnlVkGX',
  'confident' => 'pFZP5JQG7iQjIQuC4Bku',
];
```

## 4. Make the cache writable

File Manager → right-click `audio/generated` → **Change Permissions** → `775`.

This is the one step people skip. Without it, TTS falls back to the browser
voice and `status.php` reports `audio_writable: false`.

## 5. Check it before you demo it

Open `https://yourdomain.com/stella-demo/api/status.php`. You want:

```json
{ "llm": "anthropic", "tts": true, "audio_writable": true, "curl": true }
```

- `"llm": "none"` → no LLM key found (or the key is in the wrong constant)
- `"tts": false` → no ElevenLabs key, or the voice IDs are still placeholders
- `"audio_writable": false` → step 4
- `"curl": false` → rare on GoDaddy; ask support to enable the cURL extension

## 6. Ambience (optional)

Drop `cafe.mp3`, `rain.mp3`, `waves.mp3` into `audio/ambience/`. If you don't,
the player synthesises the beds in the browser and the demo still works.

## 7. Run through it once

Open `https://yourdomain.com/stella-demo/`, click **Play Erika's reading**, and
listen to the whole thing. The first generation costs a few cents; every replay
after that is served from the cache and costs nothing.

---

## If something misbehaves

**TTS times out on long stories.** `api/.htaccess` already raises
`max_execution_time` to 120s. If your host ignores `php_value` directives, add
this to the top of `api/synthesize.php` instead:

```php
@set_time_limit(120);
```

**500 error on the API calls.** Check `error_log` in the `api/` folder — cPanel
writes PHP errors there. Nine times out of ten it's a PHP version below 8.1.

**Story generates but audio never plays.** Open the browser console. If you see
a CORS or mixed-content warning, make sure you're on `https://` — the site and
the mp3s must be on the same scheme.

**Everything works locally, nothing works on the server.** Confirm the folder
structure survived the unzip: `api/` must sit next to `index.html`, not inside
another `stella-demo/`.

## Before you take it live for real

`api/config.php` ships with `DEMO_TOOLS => true`, which enables the debug
panel's cache-clearing and echoes the generated prompt back in the API
response. Flip it to `false` for a public launch.
