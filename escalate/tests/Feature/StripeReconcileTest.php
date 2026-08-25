<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Models\User;
use App\Support\Plan;
use App\Support\Stripe;
use App\Support\StripeReconcile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Paying at Stripe and being shown the free plan anyway.
 *
 * Not hypothetical. A real subscription — $12 a month, invoice paid, card on
 * file, next billing date set — existed at Stripe while the app went on
 * offering that same customer a "Choose this plan" button, because the webhook
 * that writes the local row had never been accepted.
 *
 * Nothing here makes a network call: the fetch is separated from the write in
 * StripeReconcile precisely so the write can be exercised with the payload
 * Stripe actually returns.
 */
class StripeReconcileTest extends TestCase
{
    use RefreshDatabase;

    private function withBilling(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        PlanModel::where('key', 'monthly')->update(['stripe_price' => 'price_monthly_test']);

        Plan::flush();
    }

    private function customer(string $email = 'paid@escalate.test'): User
    {
        $user = $this->makeUser($email);
        $user->forceFill(['stripe_id' => 'cus_paid'])->save();

        return $user->fresh();
    }

    /** Shaped the way Stripe's API returns a subscription. */
    private function payload(string $status = 'active', string $id = 'sub_live'): array
    {
        return [
            'id'         => $id,
            'object'     => 'subscription',
            'customer'   => 'cus_paid',
            'status'     => $status,
            'metadata'   => [],
            'trial_end'  => null,
            'items'      => ['data' => [[
                'id'       => 'si_live',
                'quantity' => 1,
                'price'    => ['id' => 'price_monthly_test', 'product' => 'prod_escalate'],
            ]]],
        ];
    }

    public function test_a_subscription_paid_for_at_stripe_becomes_real_here(): void
    {
        $this->withBilling();
        $user = $this->customer();

        $this->assertSame(Plan::FREE, $user->planKey(), 'Precondition: the bug state.');

        $this->assertTrue(StripeReconcile::record($user, [$this->payload()]));

        $user = $user->fresh();

        $this->assertTrue($user->subscribed());
        $this->assertSame('monthly', $user->planKey());
        $this->assertSame('price_monthly_test', $user->subscription()->stripe_price);
    }

    /**
     * Running twice must not bill-double or row-double.
     *
     * The webhook and this can both fire for the same subscription — whichever
     * arrives second has to be a no-op.
     */
    public function test_recording_the_same_subscription_twice_changes_nothing(): void
    {
        $this->withBilling();
        $user = $this->customer();

        StripeReconcile::record($user, [$this->payload()]);
        $this->assertFalse(StripeReconcile::record($user->fresh(), [$this->payload()]));

        $this->assertSame(1, $user->fresh()->subscriptions()->count());
    }

    /** A cancelled or unpaid subscription entitles nobody. */
    public function test_a_subscription_that_does_not_entitle_is_not_recorded(): void
    {
        $this->withBilling();
        $user = $this->customer();

        foreach (['canceled', 'incomplete', 'incomplete_expired', 'unpaid'] as $status) {
            $this->assertFalse(
                StripeReconcile::record($user->fresh(), [$this->payload($status, "sub_{$status}")]),
                "A {$status} subscription was recorded.",
            );
        }

        $this->assertSame(0, $user->fresh()->subscriptions()->count());
        $this->assertSame(Plan::FREE, $user->fresh()->planKey());
    }

    /** A trial counts: they have been through Checkout and owe money later. */
    public function test_a_trialing_subscription_entitles(): void
    {
        $this->withBilling();
        $user = $this->customer();

        $this->assertTrue(StripeReconcile::record($user, [$this->payload('trialing')]));
        $this->assertSame('monthly', $user->fresh()->planKey());
    }

    /** Somebody with no Stripe customer never reaches the network. */
    public function test_a_free_user_is_not_looked_up_at_stripe(): void
    {
        $user = $this->makeUser('free@escalate.test');

        // A key that cannot work: reaching Stripe would throw, and forUser
        // returning false rather than blowing up proves it never tried.
        Config::set('escalate.stripe.mode', Stripe::LIVE);
        Config::set('escalate.stripe.live.secret', 'sk_live_not_real');

        $this->assertFalse(StripeReconcile::forUser($user));
    }

    /**
     * Stripe being unreachable costs the reconciliation, never the page.
     *
     * The billing page is where someone goes when they are worried about
     * money. A 500 there because a third party is down is the worst available
     * response.
     */
    public function test_the_billing_page_renders_when_stripe_cannot_be_reached(): void
    {
        $this->withBilling();

        Config::set('escalate.stripe.live.secret', 'sk_live_not_real');

        $user = $this->customer('worried@escalate.test');

        $this->actingAs($user)->get(route('billing.index'))->assertOk();
    }

    /** Returning from Checkout is the moment it has to happen. */
    public function test_the_billing_page_reconciles_on_the_way_back_from_checkout(): void
    {
        $this->withBilling();

        $user = $this->customer('returning@escalate.test');

        // Stand in for Stripe: the row the reconciler would have written.
        StripeReconcile::record($user, [$this->payload()]);

        $this->actingAs($user->fresh())
            ->get(route('billing.index').'?checkout=done')
            ->assertOk()
            ->assertSee('Escalate');

        $this->assertTrue($user->fresh()->subscribed());
    }
}
