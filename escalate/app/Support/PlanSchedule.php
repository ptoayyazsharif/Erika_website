<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

/**
 * A plan change that happens later, not now.
 *
 * Stripe calls this a Subscription Schedule: a list of phases, each with its
 * own prices and dates. Taking an existing subscription into a schedule
 * (`from_subscription`) turns what they are on today into phase one, and a
 * second phase starting the moment phase one ends is the new plan.
 *
 * The important part is what does NOT happen. No proration, no invoice, no
 * credit, and no loss of access: the customer keeps exactly what they paid
 * for, for exactly as long as they paid for it, and the cheaper plan begins
 * the day the money would have been taken anyway.
 *
 * `end_behavior: release` matters. When the second phase ends the schedule
 * lets go and leaves an ordinary subscription behind, renewing monthly like
 * any other. Without it Stripe cancels at the end of the last phase, and a
 * customer who asked to pay less would be quietly unsubscribed a month later.
 */
class PlanSchedule
{
    /**
     * Move this subscription to a cheaper price when its period ends.
     *
     * @return Carbon|null the date it changes, when Stripe tells us
     */
    public static function downgradeAtPeriodEnd(Subscription $subscription, string $price): ?Carbon
    {
        $stripe = Cashier::stripe();

        // Reuse the schedule if this person has already asked once; creating
        // a second for the same subscription is an error at Stripe's end.
        $schedule = $subscription->stripe_schedule_id
            ? $stripe->subscriptionSchedules->retrieve($subscription->stripe_schedule_id)
            : $stripe->subscriptionSchedules->create(['from_subscription' => $subscription->stripe_id]);

        $current = $schedule->phases[0];

        $schedule = $stripe->subscriptionSchedules->update($schedule->id, [
            'end_behavior' => 'release',
            'phases' => [
                [
                    // Phase one restated exactly as it stands. Stripe requires
                    // the current phase to be sent back unchanged; leaving it
                    // out rewrites history and re-bills the period.
                    'items' => array_map(fn ($item) => [
                        'price'    => is_string($item->price) ? $item->price : $item->price->id,
                        'quantity' => $item->quantity ?? 1,
                    ], $current->items),
                    'start_date' => $current->start_date,
                    'end_date'   => $current->end_date,
                ],
                [
                    'items' => [['price' => $price, 'quantity' => 1]],
                    'iterations' => 1,
                ],
            ],
        ]);

        $changesOn = isset($current->end_date) ? Carbon::createFromTimestamp($current->end_date) : null;

        $subscription->forceFill([
            'stripe_schedule_id' => $schedule->id,
            'scheduled_price'    => $price,
            'current_period_end' => $changesOn ?? $subscription->current_period_end,
        ])->save();

        return $changesOn;
    }

    /**
     * Call the scheduled change off and stay put.
     *
     * Releasing leaves the subscription exactly as it is — this is an undo,
     * not a cancellation, and it must never be able to end the subscription.
     */
    public static function cancelScheduledChange(Subscription $subscription): void
    {
        if (! $subscription->stripe_schedule_id) {
            return;
        }

        Cashier::stripe()->subscriptionSchedules->release($subscription->stripe_schedule_id);

        $subscription->forceFill([
            'stripe_schedule_id' => null,
            'scheduled_price'    => null,
        ])->save();
    }
}
