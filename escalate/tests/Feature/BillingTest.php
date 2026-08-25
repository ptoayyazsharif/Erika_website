<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Support\Plan;
use App\Support\Quota;
use App\Support\Stripe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Plans, and what they entitle someone to.
 *
 * Nothing here talks to Stripe. Entitlement is read from the local
 * subscriptions table that Cashier keeps in step via webhooks, and that is the
 * property worth protecting: writing a reading must not depend on a third
 * party being reachable, so these tests build subscription rows directly and
 * assert on the numbers that come out.
 */
class BillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Billing on, with the seeded plans given price ids.
     *
     * Plans live in the `plans` table now rather than in config, so this writes
     * rows. The seeding migration has already created free/monthly/yearly from
     * config; all that is missing is the price ids, which config never had.
     */
    private function withPlans(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        PlanModel::where('key', 'monthly')->update(['stripe_price' => 'price_monthly_test']);
        PlanModel::where('key', 'yearly')->update(['stripe_price' => 'price_yearly_test']);

        Plan::flush();
    }

    /** A subscription row shaped the way Cashier's webhook would leave it. */
    private function subscribe($user, string $price, array $overrides = []): void
    {
        $user->forceFill(['stripe_id' => 'cus_'.$user->id])->save();

        $user->subscriptions()->create(array_merge([
            'type'          => 'default',
            'stripe_id'     => 'sub_'.$user->id,
            'stripe_status' => 'active',
            'stripe_price'  => $price,
            'quantity'      => 1,
        ], $overrides));
    }

    /* ── the safety property ─────────────────────────────────────────────── */

    /**
     * With billing off, plans do not exist as far as quotas are concerned.
     *
     * This is what made it safe to ship Stripe while a beta was already
     * running: nobody's allowance moved on the day this merged.
     */
    public function test_with_billing_off_everyone_gets_the_flat_configured_quota(): void
    {
        Config::set('escalate.billing.enabled', false);

        $user = $this->makeUser('flat@escalate.test');

        $this->assertSame(config('escalate.quotas.stories_per_day'), Quota::limit($user, 'story'));
        $this->assertSame(config('escalate.quotas.narrations_per_day'), Quota::limit($user, 'narration'));
        $this->assertSame(config('escalate.quotas.rewinds_per_day'), Quota::limit($user, 'rewind'));
        $this->assertSame(Plan::FREE, $user->planKey());
    }

    public function test_with_billing_off_nothing_ever_offers_an_upgrade(): void
    {
        Config::set('escalate.billing.enabled', false);

        $user = $this->makeUser('nooffer@escalate.test');

        $this->assertFalse(Plan::upgradeWouldHelp($user, 'story'));
        $this->assertStringNotContainsString('free plan', Quota::message($user, 'story'));
    }

    /* ── entitlement ─────────────────────────────────────────────────────── */

    public function test_an_unsubscribed_user_is_on_the_free_plan(): void
    {
        $this->withPlans();

        $user = $this->makeUser('free@escalate.test');

        $this->assertSame('free', $user->planKey());
        $this->assertTrue($user->onFreePlan());
        $this->assertSame(1, Quota::limit($user, 'story'));
    }

    public function test_a_subscriber_gets_their_plans_quota(): void
    {
        $this->withPlans();

        $user = $this->makeUser('paid@escalate.test');
        $this->subscribe($user, 'price_monthly_test');

        $user = $user->fresh();

        $this->assertSame('monthly', $user->planKey());
        $this->assertFalse($user->onFreePlan());
        $this->assertSame(5, Quota::limit($user, 'story'));
        $this->assertSame(8, Quota::limit($user, 'narration'));
    }

    /**
     * Cancelled but still inside the period they paid for.
     *
     * They bought this access. Taking it away the moment a cancel is pending
     * would be keeping money for something withdrawn early.
     */
    public function test_a_cancelled_subscription_keeps_its_access_until_it_ends(): void
    {
        $this->withPlans();

        $user = $this->makeUser('grace@escalate.test');
        $this->subscribe($user, 'price_monthly_test', [
            'stripe_status' => 'canceled',
            'ends_at'       => now()->addDays(9),
        ]);

        $user = $user->fresh();

        $this->assertTrue($user->subscription()->onGracePeriod());
        $this->assertSame('monthly', $user->planKey());
        $this->assertSame(5, Quota::limit($user, 'story'));
    }

    public function test_an_expired_subscription_falls_back_to_free(): void
    {
        $this->withPlans();

        $user = $this->makeUser('lapsed@escalate.test');
        $this->subscribe($user, 'price_monthly_test', [
            'stripe_status' => 'canceled',
            'ends_at'       => now()->subDay(),
        ]);

        $user = $user->fresh();

        $this->assertSame('free', $user->planKey());
        $this->assertSame(1, Quota::limit($user, 'story'));
    }

    /**
     * A live subscriber whose price was retired from config must not be
     * silently demoted while Stripe is still charging them.
     */
    public function test_an_unrecognised_price_does_not_demote_a_paying_customer(): void
    {
        $this->withPlans();

        $user = $this->makeUser('orphan@escalate.test');
        $this->subscribe($user, 'price_that_was_retired');

        $user = $user->fresh();

        $this->assertNotSame('free', $user->planKey());
        $this->assertGreaterThan(1, Quota::limit($user, 'story'));
    }

    /* ── the paywall ─────────────────────────────────────────────────────── */

    public function test_a_free_user_out_of_readings_is_offered_the_plans(): void
    {
        $this->withPlans();

        $user = $this->makeUser('offer@escalate.test');

        $this->assertTrue(Plan::upgradeWouldHelp($user, 'story'));
        $this->assertStringContainsString('free plan', Quota::message($user, 'story'));
    }

    /** Someone already on the biggest plan is told to wait, not to buy again. */
    public function test_a_paying_user_out_of_readings_is_not_sold_anything(): void
    {
        $this->withPlans();

        $user = $this->makeUser('nosell@escalate.test');
        $this->subscribe($user, 'price_monthly_test');
        $user = $user->fresh();

        $this->assertFalse(Plan::upgradeWouldHelp($user, 'story'));
        $this->assertStringNotContainsString('free plan', Quota::message($user, 'story'));
        $this->assertStringContainsString('More tomorrow', Quota::message($user, 'story'));
    }

    /* ── routes ──────────────────────────────────────────────────────────── */

    public function test_the_plan_page_shows_what_is_on_offer(): void
    {
        $this->withPlans();

        $this->actingAs($this->makeUser('page@escalate.test'))
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Escalate')
            ->assertSee('Choose this plan');
    }

    /** A plan with no price id cannot be offered, so it is not rendered. */
    public function test_a_plan_without_a_price_id_is_not_offered(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        PlanModel::query()->update(['stripe_price' => null, 'stripe_price_test' => null]);
        Plan::flush();

        $this->assertSame([], Plan::purchasable());

        $this->actingAs($this->makeUser('noplans@escalate.test'))
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('No plans are configured');
    }

    public function test_checkout_refuses_a_plan_that_is_not_configured(): void
    {
        $this->withPlans();

        $this->actingAs($this->makeUser('bogus@escalate.test'))
            ->post(route('billing.checkout'), ['plan' => 'enterprise'])
            ->assertSessionHasErrors('plan');
    }

    /** Buying the plan you are already on would create a second subscription. */
    public function test_checkout_refuses_the_plan_you_are_already_on(): void
    {
        $this->withPlans();

        $user = $this->makeUser('already@escalate.test');
        $this->subscribe($user, 'price_monthly_test');

        $this->actingAs($user->fresh())
            ->post(route('billing.checkout'), ['plan' => 'monthly'])
            ->assertRedirect(route('billing.index'));

        $this->assertSame(1, $user->subscriptions()->count());
        $this->assertStringContainsString('already on that plan', session('status'));
    }

    public function test_the_portal_is_a_no_op_for_someone_who_never_paid(): void
    {
        $this->withPlans();

        $this->actingAs($this->makeUser('noportal@escalate.test'))
            ->get(route('billing.portal'))
            ->assertRedirect(route('billing.index'));
    }

    public function test_billing_routes_reject_a_guest(): void
    {
        $this->get(route('billing.index'))->assertRedirect(route('login'));
        $this->post(route('billing.checkout'), ['plan' => 'monthly'])->assertRedirect(route('login'));
        $this->get(route('billing.portal'))->assertRedirect(route('login'));
    }

    /* ── the webhook ─────────────────────────────────────────────────────── */

    /**
     * Stripe posts with no session and no CSRF token. If the exemption is
     * dropped, every event 419s — and the symptom is subscriptions that look
     * perfect in Stripe and never appear here, because the row this app reads
     * is written by the webhook.
     */
    public function test_the_stripe_webhook_is_exempt_from_csrf(): void
    {
        Config::set('cashier.webhook.secret', 'whsec_test');

        $response = $this->postJson('/stripe/webhook', ['type' => 'ping', 'id' => 'evt_test']);

        $this->assertNotSame(
            419,
            $response->getStatusCode(),
            'The Stripe webhook is being rejected by CSRF. Cashier writes the '
                .'subscription row this app reads, so every event 419ing means '
                .'subscriptions look perfect in Stripe and never appear here.',
        );
    }

    /**
     * No signing secret must mean the door is shut, not unguarded.
     *
     * Cashier applies its signature check only when the secret is configured,
     * so an unset STRIPE_WEBHOOK_SECRET leaves the endpoint processing whatever
     * anyone posts — and those handlers write the subscriptions table this app
     * reads entitlement from. RequireStripeWebhookSecret closes it.
     */
    public function test_the_webhook_is_refused_outright_when_no_secret_is_set(): void
    {
        Config::set('cashier.webhook.secret', null);

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'id'   => 'evt_forged',
        ])->assertForbidden();
    }

    /** And a forged signature is refused when there IS a secret. */
    public function test_a_webhook_without_a_valid_signature_is_refused(): void
    {
        Config::set('cashier.webhook.secret', 'whsec_test');

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'id'   => 'evt_forged',
        ], ['Stripe-Signature' => 't=1,v1=deadbeef'])->assertForbidden();
    }

    /**
     * Stripe failing must not become a 500 in the customer's face.
     *
     * Found by clicking the button with no key configured, which is artificial
     * — but the exception class is not. An outage, a rate limit, a rotated or
     * revoked key, or a price archived out from under a plan all arrive as the
     * same ApiErrorException, and unhandled they all rendered Laravel's error
     * page at the exact moment somebody was trying to pay, with no indication
     * of whether their card had been charged.
     */
    public function test_a_stripe_failure_at_checkout_is_not_a_500(): void
    {
        $this->withPlans();

        Config::set('escalate.stripe.live.secret', null);
        Config::set('cashier.secret', null);

        $user = $this->makeUser('paying@escalate.test');

        $this->actingAs($user)
            ->post(route('billing.checkout'), ['plan' => 'monthly'])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHasErrors('billing');

        // And it says the thing that actually matters to them.
        $this->assertStringContainsString(
            'has not been charged',
            session('errors')->first('billing'),
        );
    }

    /** The billing portal is the same exposure and gets the same treatment. */
    public function test_a_stripe_failure_at_the_portal_is_not_a_500(): void
    {
        $this->withPlans();

        Config::set('escalate.stripe.live.secret', null);
        Config::set('cashier.secret', null);

        $user = $this->makeUser('managing@escalate.test');
        $user->forceFill(['stripe_id' => 'cus_gone'])->save();

        $this->actingAs($user->fresh())
            ->get(route('billing.portal'))
            ->assertRedirect(route('billing.index'))
            ->assertSessionHasErrors('billing');
    }
}
