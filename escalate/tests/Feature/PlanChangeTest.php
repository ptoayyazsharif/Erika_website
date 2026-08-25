<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Models\User;
use App\Support\Plan;
use App\Support\PlanChange;
use App\Support\Stripe;
use App\Support\SubscriptionPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Upgrading, downgrading, and saying when the next charge falls.
 *
 * Both of these were reported from the live app by its owner: the billing page
 * never said when the next payment was, and somebody on the yearly plan was
 * being offered a button that would hand it back mid-term.
 */
class PlanChangeTest extends TestCase
{
    use RefreshDatabase;

    private function withPlans(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        PlanModel::where('key', 'monthly')->update([
            'stripe_price' => 'price_monthly', 'amount' => 1200, 'currency' => 'usd', 'interval' => 'month',
        ]);
        PlanModel::where('key', 'yearly')->update([
            'stripe_price' => 'price_yearly', 'amount' => 12000, 'currency' => 'usd', 'interval' => 'year',
        ]);

        Plan::flush();
    }

    private function subscriber(string $email, string $price): User
    {
        $user = $this->makeUser($email);
        $user->forceFill(['stripe_id' => 'cus_'.md5($email)])->save();
        $user = $user->fresh();

        $sub = $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'sub_'.md5($email),
            'stripe_status' => 'active', 'stripe_price' => $price, 'quantity' => 1,
        ]);
        $sub->items()->create([
            'stripe_id' => 'si_'.md5($email), 'stripe_product' => 'prod_x',
            'stripe_price' => $price, 'quantity' => 1,
        ]);

        return $user->fresh();
    }

    /* ── which way is which ──────────────────────────────────────────────── */

    public function test_monthly_to_yearly_is_an_upgrade_and_yearly_to_monthly_is_not(): void
    {
        $this->withPlans();

        $this->assertSame(PlanChange::UPGRADE,   PlanChange::direction('monthly', 'yearly'));
        $this->assertSame(PlanChange::DOWNGRADE, PlanChange::direction('yearly', 'monthly'));
        $this->assertSame(PlanChange::UPGRADE,   PlanChange::direction('free', 'monthly'));
        $this->assertSame(PlanChange::SAME,      PlanChange::direction('yearly', 'yearly'));
    }

    /**
     * Ranked by what you get, then by how long you commit — never by price.
     *
     * Price cannot decide it. Annualised, the yearly plan is the cheaper one,
     * so ranking by cost makes moving to it look like a downgrade; ranking by
     * the invoice amount would make a $99/year plan outrank a $50/month one
     * worth six times as much. Here the two paid plans grant the same, so the
     * term breaks the tie and the year is the bigger commitment.
     */
    public function test_plans_are_ranked_by_entitlement_then_by_term(): void
    {
        $this->withPlans();

        $this->assertSame(PlanChange::tier('monthly'), PlanChange::tier('yearly'));
        $this->assertGreaterThan(PlanChange::tier('free'), PlanChange::tier('monthly'));

        $this->assertGreaterThan(PlanChange::term('monthly'), PlanChange::term('yearly'));
    }

    /* ── the button says what will happen ────────────────────────────────── */

    public function test_the_button_tells_a_subscriber_when_the_change_lands(): void
    {
        $this->withPlans();

        $yearly = $this->subscriber('onyearly@escalate.test', 'price_yearly');

        $this->assertSame('Switch at renewal', PlanChange::label($yearly, 'monthly'));

        $monthly = $this->subscriber('onmonthly@escalate.test', 'price_monthly');

        $this->assertSame('Upgrade to this', PlanChange::label($monthly, 'yearly'));
        $this->assertSame('Choose this plan', PlanChange::label($this->makeUser('free@escalate.test'), 'monthly'));
    }

    /**
     * The bug as it was reported: a yearly subscriber offered "Switch to this".
     *
     * The old page said the same words for every card, so moving to a cheaper
     * plan looked exactly like buying a better one.
     */
    public function test_the_billing_page_no_longer_offers_a_yearly_subscriber_an_instant_downgrade(): void
    {
        $this->withPlans();

        $user = $this->subscriber('reported@escalate.test', 'price_yearly');

        $page = $this->actingAs($user)->get(route('billing.index'))->assertOk();

        $page->assertSee('Switch at renewal');
        $page->assertDontSee('Switch to this');
    }

    /* ── when does it renew ──────────────────────────────────────────────── */

    public function test_the_renewal_date_is_taken_from_a_stripe_payload_and_shown(): void
    {
        $this->withPlans();

        $user = $this->subscriber('renewing@escalate.test', 'price_yearly');
        $renews = now()->addYear()->startOfDay();

        SubscriptionPeriod::syncFrom($user->subscription(), [
            'id' => $user->subscription()->stripe_id,
            'items' => ['data' => [['current_period_end' => $renews->timestamp]]],
        ]);

        $this->assertTrue($user->fresh()->subscription()->current_period_end->isSameDay($renews));

        $this->actingAs($user->fresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Renews automatically on '.$renews->format('j F Y'));
    }

    /**
     * A payload without a period must not erase a date we already hold.
     *
     * Stripe sends partial objects on some events; treating "absent" as
     * "cleared" would blank the renewal date at random.
     */
    public function test_a_payload_with_no_period_leaves_the_known_date_alone(): void
    {
        $this->withPlans();

        $user = $this->subscriber('keepdate@escalate.test', 'price_yearly');
        $renews = now()->addYear();

        SubscriptionPeriod::syncFrom($user->subscription(), [
            'items' => ['data' => [['current_period_end' => $renews->timestamp]]],
        ]);
        SubscriptionPeriod::syncFrom($user->fresh()->subscription(), ['items' => ['data' => []]]);

        $this->assertNotNull($user->fresh()->subscription()->current_period_end);
    }

    /** With no date known, the page says so rather than saying nothing. */
    public function test_a_subscription_with_no_known_date_still_says_it_renews(): void
    {
        $this->withPlans();

        $user = $this->subscriber('nodate@escalate.test', 'price_yearly');

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Renews automatically');
    }

    /* ── a scheduled change, once one exists ─────────────────────────────── */

    public function test_a_scheduled_downgrade_is_explained_with_a_way_out(): void
    {
        $this->withPlans();

        $user = $this->subscriber('scheduled@escalate.test', 'price_yearly');
        $on = now()->addMonths(11);

        $user->subscription()->forceFill([
            'scheduled_price'    => 'price_monthly',
            'stripe_schedule_id' => 'sub_sched_123',
            'current_period_end' => $on,
        ])->save();

        $page = $this->actingAs($user->fresh())->get(route('billing.index'))->assertOk();

        $page->assertSee('Changes to');
        $page->assertSee($on->format('j F Y'));
        $page->assertSee('Keep my current plan instead');

        // And they are still on the plan they paid for until then.
        $this->assertSame('yearly', $user->fresh()->planKey());
    }

    /** The undo does nothing rash when there is nothing scheduled. */
    public function test_keeping_a_plan_with_nothing_scheduled_is_harmless(): void
    {
        $this->withPlans();

        $user = $this->subscriber('nothing@escalate.test', 'price_yearly');

        $this->actingAs($user)->post(route('billing.keep'))->assertRedirect(route('billing.index'));

        $this->assertSame('yearly', $user->fresh()->planKey());
        $this->assertTrue($user->fresh()->subscribed());
    }
}
