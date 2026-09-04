<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A device signing itself up for reminders, or standing down.
 *
 * Everything arrives from the browser, so everything is validated and written
 * with forceFill. The endpoint in particular is a URL this server will later
 * make outbound requests to — that is worth treating as untrusted input rather
 * than as a string.
 */
class PushController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
            'p256dh'   => ['required', 'string', 'max:255'],
            'auth'     => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $endpoint = scalar_input($data['endpoint']);

        // The push service must be one of the real ones. Without this, the
        // endpoint is an arbitrary URL a signed-in person can make this server
        // POST to on a schedule — server-side request forgery with a cron
        // attached. The allowlist is the whole defence and it is deliberately
        // narrow.
        if (! self::isKnownPushService($endpoint)) {
            return response()->json(['error' => 'Unrecognised push service.'], 422);
        }

        $timezone = scalar_input($data['timezone'] ?? '');

        // Found-or-new by hand rather than updateOrCreate: that helper
        // mass-assigns the key it searches on, and this model's $fillable is
        // deliberately empty because everything here arrives from the browser.
        $hash = PushSubscription::hash($endpoint);

        $subscription = PushSubscription::firstWhere('endpoint_hash', $hash) ?? new PushSubscription;

        $subscription->forceFill([
            'endpoint_hash' => $hash,
            'user_id'      => $request->user()->id,
            'endpoint'     => $endpoint,
            'p256dh'       => scalar_input($data['p256dh']),
            'auth'         => scalar_input($data['auth']),
            // in_array against the real zone list: this is written to a column
            // the scheduler later hands to Carbon, and an unknown zone there
            // throws inside a loop over everybody.
            'timezone'     => in_array($timezone, timezone_identifiers_list(), true) ? $timezone : null,
            'last_used_at' => null,
        ])->save();

        return response()->json(['ok' => true]);
    }

    /** This device is standing down — the others keep working. */
    public function destroy(Request $request): JsonResponse
    {
        $endpoint = scalar_input($request->input('endpoint'));

        if (filled($endpoint)) {
            PushSubscription::where('user_id', $request->user()->id)
                ->where('endpoint_hash', PushSubscription::hash($endpoint))
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * The switch in My World: stop reminders everywhere without having to find
     * the browser's own notification settings, which most people cannot undo.
     */
    public function preference(Request $request): RedirectResponse
    {
        $wants = $request->boolean('push_reminders');

        $request->user()->profile()->firstOrCreate([])
            ->forceFill(['push_reminders' => $wants])->save();

        if (! $wants) {
            PushSubscription::where('user_id', $request->user()->id)->delete();
        }

        return back()->with('status', $wants
            ? 'Reminders on. Your device will ask permission if it has not already.'
            : 'Reminders off.');
    }

    /**
     * The three push services browsers actually use. Anything else is refused.
     *
     * Checked on the host, after parsing — a string comparison against the URL
     * would pass `https://evil.test/?x=https://fcm.googleapis.com`.
     */
    private static function isKnownPushService(string $endpoint): bool
    {
        $host = parse_url($endpoint, PHP_URL_HOST);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($scheme !== 'https' || ! is_string($host)) {
            return false;
        }

        foreach (['fcm.googleapis.com', 'push.services.mozilla.com', 'notify.windows.com'] as $known) {
            if ($host === $known || str_ends_with($host, '.'.$known)) {
                return true;
            }
        }

        // Apple's endpoints are per-region: web.push.apple.com today, and they
        // have added hosts before. Matched on the suffix rather than pinned.
        return str_ends_with($host, '.push.apple.com') || $host === 'web.push.apple.com';
    }
}
