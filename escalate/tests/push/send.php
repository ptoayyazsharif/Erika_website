<?php

/*
 * Send a real notification, and read what went over the wire.
 *
 * ── Why this exists outside the PHPUnit suite ───────────────────────────────
 *
 * Everything about push was tested at the edges — who is selected, what the
 * service worker does with a payload, that a 410 prunes — and the middle was
 * never run once. VAPID signing and payload encryption are the part of this
 * feature most likely to be quietly wrong, and neither can be reached with the
 * HTTP client faked, because minishlink/web-push builds its own through
 * php-http/discovery rather than Laravel's.
 *
 * So this talks to a listening socket. It needs a port, which is why it is a
 * hand-run script beside tests/browser/* rather than a test in the suite.
 *
 * The subscription carries a REAL P-256 keypair generated here with openssl —
 * exactly the shape a browser hands over — so the library has to genuinely
 * encrypt to it. A placeholder string would make the encryption throw, and a
 * test that skipped it would prove nothing.
 *
 * ── The honest limit ────────────────────────────────────────────────────────
 *
 * This proves the request is correctly built, signed and encrypted. It does not
 * prove a phone buzzes: that needs a real push service and a real device, and
 * Chromium here reaches no external host. That hop stays a human's.
 *
 * Run through real-send.sh, never on its own — it deletes push_subscriptions.
 */

use App\Models\Announcement;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Push;

$captured = getenv('PUSH_CAPTURE') ?: '/tmp/escalate-push-captured.json';
$port = (int) (getenv('PUSH_PORT') ?: 9411);

$fails = 0;
$check = function (string $name, bool $ok, string $detail = '') use (&$fails) {
    echo ($ok ? "PASS  " : "FAIL  "), $name, PHP_EOL;
    if (! $ok) {
        $fails++;
        if ($detail !== '') {
            echo '      ', $detail, PHP_EOL;
        }
    }
};

$b64u = fn (string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
$unb64u = fn (string $s) => base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4));

/** A subscription keypair as a browser would produce one. */
$browserKeypair = function () use ($b64u): array {
    $key = openssl_pkey_new([
        'curve_name'       => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);

    $d = openssl_pkey_get_details($key);

    // The uncompressed point: 0x04 ‖ X ‖ Y, which is subscription.keys.p256dh.
    $point = "\x04".str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT)
                   .str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);

    return ['p256dh' => $b64u($point), 'auth' => $b64u(random_bytes(16))];
};

if (! Push::configured()) {
    echo "No VAPID keys configured, so nothing can be signed. Set them first.\n";
    exit(2);
}

$user = User::query()->first();

if (! $user) {
    echo "No user to attach a device to.\n";
    exit(2);
}

$announcement = Announcement::latest('id')->first();
$title = $announcement?->title ?? 'A test notification';
$body = $announcement?->notificationBody() ?? 'Sent from tests/push/real-send.sh.';

PushSubscription::query()->delete();

foreach (['ok', 'gone', 'broken'] as $fate) {
    $keys = $browserKeypair();
    $endpoint = "http://127.0.0.1:{$port}/wp/{$fate}";

    (new PushSubscription)->forceFill([
        'user_id'       => $user->id,
        'endpoint'      => $endpoint,
        'endpoint_hash' => PushSubscription::hash($endpoint),
        'p256dh'        => $keys['p256dh'],
        'auth'          => $keys['auth'],
        'timezone'      => 'Europe/London',
    ])->save();
}

$result = Push::send(
    PushSubscription::query()->reachable()->get(),
    $title,
    $body,
    'https://escalate.cloud/today',
    'escalate-real-send',
);

echo PHP_EOL, "sent {$result['sent']}, pruned {$result['pruned']}, failed {$result['failed']}", PHP_EOL, PHP_EOL;

/* ── what the push service actually received ─────────────────────────────── */

$requests = json_decode((string) @file_get_contents($captured), true) ?: [];

$check('all three devices were contacted', count($requests) === 3, count($requests).' requests');

$vapidPublic = (string) config('escalate.push.public_key');
$lengths = [];

foreach ($requests as $r) {
    $fate = basename($r['url']);
    $h = $r['headers'];
    $payload = base64_decode($r['bodyBase64']);
    $lengths[] = strlen($payload);

    $auth = $h['authorization'] ?? '';
    $cryptoKey = $h['crypto-key'] ?? '';

    // aesgcm (draft-04) signs with `Authorization: WebPush <jwt>` and carries
    // the VAPID public key in Crypto-Key;p256ecdsa. The newer `vapid t=…, k=…`
    // form belongs to aes128gcm and is NOT what this should look like.
    $check("[{$fate}] Authorization is a WebPush JWT", str_starts_with($auth, 'WebPush '), $auth);

    $parts = explode('.', substr($auth, strlen('WebPush ')));
    $check("[{$fate}] the JWT is signed", count($parts) === 3 && strlen($unb64u($parts[2])) === 64);

    $header = json_decode($unb64u($parts[0] ?? ''), true) ?: [];
    $claims = json_decode($unb64u($parts[1] ?? ''), true) ?: [];

    $check("[{$fate}] signed ES256", ($header['alg'] ?? null) === 'ES256', json_encode($header));
    $check("[{$fate}] aud is the push service's own origin",
        ($claims['aud'] ?? null) === "http://127.0.0.1:{$port}" || ($claims['aud'] ?? null) === 'http://127.0.0.1',
        json_encode($claims));
    $check("[{$fate}] exp is in the future", ($claims['exp'] ?? 0) > time(), (string) ($claims['exp'] ?? 0));
    $check("[{$fate}] sub identifies this app", filled($claims['sub'] ?? null), json_encode($claims));

    preg_match('/p256ecdsa=([A-Za-z0-9_-]+)/', $cryptoKey, $m);
    $check("[{$fate}] the key on the wire is the configured VAPID public key",
        ($m[1] ?? null) === $vapidPublic, substr($m[1] ?? $cryptoKey, 0, 40));

    $check("[{$fate}] aesgcm content encoding", ($h['content-encoding'] ?? null) === 'aesgcm', $h['content-encoding'] ?? '');
    $check("[{$fate}] salt and dh are present",
        str_contains($h['encryption'] ?? '', 'salt=') && str_contains($cryptoKey, 'dh='));

    $check("[{$fate}] the payload is real ciphertext", strlen($payload) > 100, strlen($payload).' bytes');

    // The whole point of the privacy rule is that this content is fine on a
    // lock screen, NOT that it is fine in transit. It must still be encrypted.
    $check("[{$fate}] the title is not readable in transit", ! str_contains($payload, $title));
    $check("[{$fate}] the body is not readable in transit", ! str_contains($payload, substr($body, 0, 20)));
}

$check('every payload is padded to the same length, so the ciphertext does not leak how long the message was',
    count(array_unique($lengths)) === 1, implode(', ', array_unique($lengths)));

/* ── and what the answers did to the table ───────────────────────────────── */

$remaining = PushSubscription::pluck('endpoint')->map(fn ($e) => basename($e))->all();
sort($remaining);

$check('a 410 deleted that device', ! in_array('gone', $remaining, true), implode(',', $remaining));
$check('a 500 did NOT delete that device — a bad minute at a vendor must not unsubscribe anybody',
    in_array('broken', $remaining, true), implode(',', $remaining));
$check('the device that succeeded is still there', in_array('ok', $remaining, true), implode(',', $remaining));
$check('the counts match what happened',
    $result === ['sent' => 1, 'pruned' => 1, 'failed' => 1], json_encode($result));

echo PHP_EOL, $fails === 0 ? "All checks passed." : "{$fails} failed.", PHP_EOL;

exit($fails === 0 ? 0 : 1);
