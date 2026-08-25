<?php

namespace App\Http\Controllers;

use App\Support\Plan;
use App\Support\PlanChange;
use App\Support\PlanSchedule;
use App\Support\Quota;
use App\Support\StripeReconcile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;

/**
 * Plans, and the two doors into Stripe.
 *
 * Deliberately thin. Card details, invoices, tax, dunning, cancellation and
 * refunds are all Stripe's screens, reached through Checkout and the Billing
 * Portal — this app never sees a card number and has no page that could leak
 * one. That is worth a great deal more than a bespoke billing UI: the moment
 * this application renders a card field it is in scope for PCI DSS SAQ A-EP
 * instead of SAQ A, and nothing about this product justifies that.
 *
 * Entitlement is read locally (see App\Support\Plan) rather than by asking
 * Stripe on each request. Only these three routes talk to Stripe, and none of
 * them is on the path of writing a reading.
 */
class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        /*
         * Settle anything Stripe knows and this app does not.
         *
         * Narrow on purpose. Coming back from Checkout is the moment it
         * matters, and "has a Stripe customer but no subscription row at all"
         * is the state left behind when the webhook never landed — the state
         * that shows a paying customer the free plan. Someone who cancelled
         * still has a row, so they do not keep hitting Stripe on every visit,
         * and a free user has no Stripe id and never touches this at all.
         */
        if ($request->query('checkout') === 'done' || ($user->hasStripeId() && ! $user->subscriptions()->exists())) {
            if (StripeReconcile::forUser($user)) {
                $user->refresh()->load('subscriptions');
            }
        }

        return view('billing.index', [
            'user'         => $user,
            'current'      => $user->planKey(),
            'plans'        => Plan::purchasable(),
            'free'         => Plan::config(Plan::FREE),
            'subscription' => $user->subscription(),
            // The plan a scheduled downgrade is heading for, so the page can
            // name it rather than showing a bare Stripe price id.
            'scheduledPlan' => ($p = $user->subscription()?->scheduled_price)
                ? (Plan::all()[Plan::keyForPrice($p)] ?? null)
                : null,
            // What their plan actually buys them today, so the page answers
            // "what am I paying for" with numbers rather than adjectives.
            'remaining'    => [
                'story'     => Quota::remaining($user, 'story'),
                'narration' => Quota::remaining($user, 'narration'),
                'rewind'    => Quota::remaining($user, 'rewind'),
            ],
        ]);
    }

    /** Hand off to Stripe Checkout. */
    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            // Validated against the configured keys, never trusted: this value
            // selects the price the customer is charged.
            'plan' => ['required', 'string', 'in:'.implode(',', array_keys(Plan::purchasable()))],
        ]);

        // Already on this exact plan — sending them to Checkout would create a
        // second subscription and bill them twice for the same thing.
        if ($user->planKey() === $data['plan']) {
            return redirect()->route('billing.index')
                ->with('status', 'You are already on that plan.');
        }

        $price = Plan::config($data['plan'])['price'];

        /*
         * An existing subscriber is swapped, not re-checked-out.
         *
         * swapAndInvoice() moves them between monthly and yearly on the
         * subscription they already have, with Stripe prorating. Sending them
         * through Checkout instead would leave two live subscriptions on one
         * customer and charge for both — the single most expensive mistake
         * available in this file, and the one that generates refund requests
         * rather than support tickets.
         */
        if ($user->subscribed()) {
            $subscription = $user->subscription();

            /*
             * A downgrade waits for the period they have already bought.
             *
             * swapAndInvoice() here was the bug: somebody eleven months into a
             * year who tapped the monthly card handed over their annual plan
             * on the spot and got Stripe account credit instead of a refund.
             * See App\Support\PlanChange for why nobody does it that way.
             */
            if (PlanChange::direction($user->planKey(), $data['plan']) === PlanChange::DOWNGRADE) {
                try {
                    $on = PlanSchedule::downgradeAtPeriodEnd($subscription, $price);
                } catch (ApiErrorException $e) {
                    return $this->stripeIsNotAnswering($e);
                }

                return redirect()->route('billing.index')->with('status', $on
                    ? 'Done. You keep everything you have now until '.$on->format('j F Y').', and move to the new plan then. Nothing has been charged.'
                    : 'Done. You move to the new plan when this one renews. Nothing has been charged.');
            }

            try {
                $subscription->swapAndInvoice($price);
            } catch (IncompletePayment $e) {
                return redirect()->route(
                    'cashier.payment',
                    [$e->payment->id, 'redirect' => route('billing.index')],
                );
            } catch (ApiErrorException $e) {
                return $this->stripeIsNotAnswering($e);
            }

            return redirect()->route('billing.index')->with('status', 'Your plan is changed.');
        }

        try {
            $checkout = $user->newSubscription('default', $price);

            if ($days = (int) config('escalate.billing.trial_days')) {
                $checkout->trialDays($days);
            }

            $session = $checkout->checkout([
                'success_url' => route('billing.index').'?checkout=done',
                'cancel_url'  => route('billing.index').'?checkout=cancelled',
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeIsNotAnswering($e);
        }

        return $session->redirect();
    }

    /**
     * Stripe refused, and the customer is standing at the till.
     *
     * Every call in this file can fail for reasons that have nothing to do
     * with the person clicking: an outage, a rate limit, a key rotated or
     * revoked, a price archived out from under a plan. Unhandled, all of them
     * render Laravel's error page — a 500 at the exact moment somebody is
     * trying to give you money, with no indication of whether they were
     * charged.
     *
     * So: back to the billing page, told plainly that nothing happened to
     * their card, with the real reason in the log rather than on the screen —
     * a Stripe message can name a price id or a key prefix, and neither
     * belongs in front of a customer.
     */
    private function stripeIsNotAnswering(\Throwable $e): RedirectResponse
    {
        report($e);

        return redirect()->route('billing.index')->withErrors(['billing' =>
            'Stripe could not be reached just now, so nothing has changed and '
            .'your card has not been charged. Please try again in a minute.']);
    }

    /** Call off a scheduled downgrade and stay on the current plan. */
    public function keepPlan(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscription();

        if (! $subscription?->scheduled_price) {
            return redirect()->route('billing.index');
        }

        try {
            PlanSchedule::cancelScheduledChange($subscription);
        } catch (ApiErrorException $e) {
            return $this->stripeIsNotAnswering($e);
        }

        return redirect()->route('billing.index')
            ->with('status', 'Kept. Your plan carries on exactly as it was.');
    }

    /**
     * Stripe's own billing portal: card, invoices, cancelling.
     *
     * Cancelling lives there rather than here on purpose. A cancel button in
     * this app would have to reproduce Stripe's proration and grace-period
     * rules to tell the truth about what happens next, and a cancel flow that
     * misstates the refund is worse than one extra click.
     */
    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return redirect()->route('billing.index')
                ->with('status', 'There is nothing to manage yet — you are on the free plan.');
        }

        try {
            return $user->redirectToBillingPortal(route('billing.index'));
        } catch (ApiErrorException $e) {
            return $this->stripeIsNotAnswering($e);
        }
    }
}
