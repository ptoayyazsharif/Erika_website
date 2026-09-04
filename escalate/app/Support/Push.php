<?php

namespace App\Support;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sending a notification to a device, and forgetting the ones that have gone.
 *
 * ── What a notification may say ─────────────────────────────────────────────
 *
 * Nothing private. A notification lands on a lock screen, which is a public
 * surface — readable by whoever is near the phone, on a train or across a
 * kitchen table. This is an app whose entire promise is that what is inside it
 * stays inside, so no desire title, no story text, no names ever reach a
 * payload. What goes out is one neutral line an administrator wrote.
 *
 * That is a deliberate cost: a personalised notification would be tapped more
 * often. It is not worth putting what somebody is quietly working on where
 * their partner can read it.
 */
class Push
{
    /** Whether the keys exist at all. Without them this is inert. */
    public static function configured(): bool
    {
        return filled(config('escalate.push.public_key'))
            && filled(config('escalate.push.private_key'));
    }

    /**
     * Send one message to many devices.
     *
     * Dead subscriptions are deleted as the push service reports them. That is
     * not tidiness: a subscription dies when somebody uninstalls or clears
     * their browser, the endpoint answers 404 or 410 for ever after, and a
     * table that keeps them makes every later send slower for no delivery.
     *
     * Any other failure — a 500 at the push service, a timeout — leaves the row
     * alone. Deleting on a transient fault would silently unsubscribe somebody
     * because a vendor had a bad minute.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @return array{sent: int, pruned: int, failed: int}
     */
    public static function send(iterable $subscriptions, string $title, string $body, string $url): array
    {
        if (! self::configured()) {
            return ['sent' => 0, 'pruned' => 0, 'failed' => 0];
        }

        $webPush = new WebPush(['VAPID' => [
            'subject'    => config('app.url'),
            'publicKey'  => config('escalate.push.public_key'),
            'privateKey' => config('escalate.push.private_key'),
        ]]);

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
        ], JSON_THROW_ON_ERROR);

        $byEndpoint = [];

        foreach ($subscriptions as $subscription) {
            $byEndpoint[$subscription->endpoint] = $subscription;

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $subscription->endpoint,
                    'publicKey'       => $subscription->p256dh,
                    'authToken'       => $subscription->auth,
                    'contentEncoding' => 'aesgcm',
                ]),
                $payload,
            );
        }

        $result = ['sent' => 0, 'pruned' => 0, 'failed' => 0];

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            $subscription = $byEndpoint[$endpoint] ?? null;

            if ($report->isSuccess()) {
                $result['sent']++;
                $subscription?->forceFill(['last_used_at' => now()])->save();

                continue;
            }

            // isSubscriptionExpired() is the library's own reading of 404/410.
            if ($report->isSubscriptionExpired()) {
                $result['pruned']++;
                $subscription?->delete();

                continue;
            }

            $result['failed']++;
            logger()->warning('Push failed', [
                'endpoint' => \Illuminate\Support\Str::limit($endpoint, 60),
                'reason'   => \Illuminate\Support\Str::limit($report->getReason(), 200),
            ]);
        }

        return $result;
    }
}
