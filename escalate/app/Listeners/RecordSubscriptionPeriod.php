<?php

namespace App\Listeners;

use App\Support\SubscriptionPeriod;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;

/**
 * Take the renewal date off every subscription webhook as it goes past.
 *
 * A listener rather than a fork of Cashier's WebhookController: that class
 * does a great deal we want unchanged, and subclassing it to add one field
 * means re-testing all of it every time Cashier moves. This runs alongside,
 * reads the payload Cashier is already handling, and touches one column.
 *
 * Renewals matter as much as the first payment here. Each month or year
 * Stripe moves the period forward and sends subscription.updated; without
 * this the billing page would go on quoting the first date it ever saw.
 */
class RecordSubscriptionPeriod
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';

        if (! str_starts_with($type, 'customer.subscription.')) {
            return;
        }

        $data = $event->payload['data']['object'] ?? [];
        $id   = $data['id'] ?? null;

        if (! $id) {
            return;
        }

        /*
         * Cashier's own handler runs from the same event and may be creating
         * this row right now. Missing it here is harmless — the next event, or
         * the reconciler on the customer's next visit, fills the date in.
         */
        $subscription = Subscription::where('stripe_id', $id)->first();

        if ($subscription) {
            SubscriptionPeriod::syncFrom($subscription, $data);
        }
    }
}
