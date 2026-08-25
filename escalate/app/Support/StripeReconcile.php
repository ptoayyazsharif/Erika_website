<?php

namespace App\Support;

use App\Models\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;

/**
 * Bring a subscription that exists at Stripe into this application.
 *
 * Cashier records a subscription when Stripe's `customer.subscription.created`
 * webhook arrives, and only then. That is a single point of failure on the one
 * path where failing is least acceptable: the customer has paid, Stripe is
 * charging them monthly, and the app still shows them the free plan with a
 * "Choose this plan" button under it. It happened here — the webhook endpoint
 * 403s until a signing secret is configured, so the event was never accepted
 * and a live $12/month subscription simply did not exist as far as the app was
 * concerned.
 *
 * Even with the webhook wired up correctly the gap is real, just shorter:
 * Stripe delivers asynchronously, and the customer lands back on the billing
 * page immediately. Reading the truth from Stripe on the way back closes both.
 *
 * This does NOT replace the webhook, and configuring one is still required.
 * Everything *after* the first payment — renewals, cancellations, failed
 * cards, plan swaps made in the portal — arrives only that way. This settles
 * the first moment; the webhook keeps it true afterwards.
 */
class StripeReconcile
{
    /** Statuses that mean the customer should have what they paid for. */
    private const ENTITLING = ['active', 'trialing', 'past_due'];

    /**
     * Copy any live Stripe subscription onto the user, if it is missing here.
     *
     * Safe to call more than once and safe to call alongside the webhook: the
     * write underneath is an updateOrCreate keyed on the Stripe subscription
     * id, so whichever arrives second is a no-op rather than a duplicate.
     */
    public static function forUser(User $user): bool
    {
        if (! $user->hasStripeId()) {
            return false;
        }

        try {
            $subscriptions = Cashier::stripe()->subscriptions->all([
                'customer' => $user->stripe_id,
                'status'   => 'all',
                'limit'    => 10,
                // The webhook payload carries expanded prices; match it, or the
                // handler reads a bare id string where it expects an object.
                'expand'   => ['data.items.data.price'],
            ]);
        } catch (\Throwable $e) {
            // The billing page must render whatever Stripe is doing. A missing
            // key, a network fault or an outage should cost the reconciliation,
            // never the page.
            report($e);

            return false;
        }

        return self::record($user, array_map(
            fn ($subscription) => $subscription->toArray(),
            $subscriptions->data,
        ));
    }

    /**
     * Write the ones that entitle, skip the ones already here.
     *
     * Separate from the fetch above so it can be tested without a network
     * call, and so a Stripe payload can be exercised directly.
     *
     * @param  array<int, array<string, mixed>>  $subscriptions
     */
    public static function record(User $user, array $subscriptions): bool
    {
        $recorder = new class extends WebhookController
        {
            /*
             * Cashier's mapping, reused rather than copied. It decides the
             * subscription type, the price, the quantity, the trial end and
             * every subscription item — replicating that here would be four
             * lines that drift out of agreement with the webhook the first
             * time Cashier changes one of them.
             */
            public function record(array $subscription): void
            {
                $this->handleCustomerSubscriptionCreated([
                    'data' => ['object' => $subscription],
                ]);
            }
        };

        $recorded = false;

        foreach ($subscriptions as $subscription) {
            if (! in_array($subscription['status'] ?? '', self::ENTITLING, true)) {
                continue;
            }

            if ($user->subscriptions()->where('stripe_id', $subscription['id'])->exists()) {
                continue;
            }

            $recorder->record($subscription);
            $recorded = true;
        }

        return $recorded;
    }
}
