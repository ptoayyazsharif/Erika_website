<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;

/**
 * Keep the local subscription in step with when Stripe will next charge.
 *
 * Cashier stores what someone is on, never when it renews — and its
 * currentPeriodEnd() answers by calling Stripe once per subscription item,
 * every time it is asked. That is fine for a console command and wrong for a
 * page render: a round trip per view, and a billing page that cannot draw
 * while Stripe is having a bad morning.
 *
 * So the date is copied out of the payloads that already arrive — the webhook
 * and the reconciler both pass through here — and read locally afterwards.
 *
 * Stripe moved billing periods onto subscription ITEMS, so the date lives at
 * items.data[].current_period_end. A single-price subscription has one item;
 * where there are several, the last one to renew is the one that matters,
 * because that is when the customer next sees a charge.
 */
class SubscriptionPeriod
{
    /**
     * Record the renewal date carried by a Stripe subscription payload.
     *
     * @param  array<string, mixed>  $payload  a Stripe subscription, as an array
     */
    public static function syncFrom(Subscription $subscription, array $payload): void
    {
        $end = self::endFrom($payload);

        // A payload that carries no period — a partial object, or an event
        // shape that omits items — must not blank a date we already have.
        if ($end === null) {
            return;
        }

        if (! $subscription->current_period_end || ! $subscription->current_period_end->equalTo($end)) {
            $subscription->forceFill(['current_period_end' => $end])->save();
        }
    }

    /** The latest period end across the payload's items, or null. */
    public static function endFrom(array $payload): ?Carbon
    {
        $latest = null;

        foreach ($payload['items']['data'] ?? [] as $item) {
            $ts = $item['current_period_end'] ?? null;

            if (! is_int($ts) && ! ctype_digit((string) $ts)) {
                continue;
            }

            $date = Carbon::createFromTimestamp((int) $ts);

            if (! $latest || $date->gt($latest)) {
                $latest = $date;
            }
        }

        // Older API versions put it on the subscription itself. Cheap to honour.
        if (! $latest && isset($payload['current_period_end'])) {
            $latest = Carbon::createFromTimestamp((int) $payload['current_period_end']);
        }

        return $latest;
    }
}
