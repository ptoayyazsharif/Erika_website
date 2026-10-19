<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Models\User;
use App\Support\Stripe;
use App\Support\StripeSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Pricing a plan from the admin panel instead of the Stripe dashboard.
 *
 * None of these talk to Stripe. They cover the parts that are wrong before any
 * network call happens — the money arithmetic, and the guards that decide
 * whether to call at all.
 */
class PlanPricingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('pricing@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /** Typed as 12.00, stored as 1200. Money never becomes a float in the row. */
    public function test_an_amount_is_stored_in_minor_units(): void
    {
        $this->admin();

        $this->post(route('admin.plans.store'), [
            'key' => 'priced', 'label' => 'Priced', 'interval' => 'month',
            'amount_major' => '12.00', 'currency' => 'gbp',
            'quotas' => ['story' => 5], 'is_active' => '1',
        ])->assertRedirect();

        $plan = PlanModel::where('key', 'priced')->first();

        $this->assertSame(1200, $plan->amount);
        $this->assertSame('gbp', $plan->currency);
    }

    /** The awkward ones: pence that are not round, and a zero. */
    public function test_fractional_and_zero_amounts_convert_exactly(): void
    {
        $this->admin();

        foreach ([['9.99', 999], ['0.50', 50], ['120', 12000], ['0', 0]] as [$typed, $expected]) {
            PlanModel::where('key', 'conv')->delete();

            $this->post(route('admin.plans.store'), [
                'key' => 'conv', 'label' => 'Conv', 'interval' => 'month',
                'amount_major' => $typed, 'quotas' => ['story' => 1], 'is_active' => '1',
            ])->assertRedirect();

            $this->assertSame($expected, PlanModel::where('key', 'conv')->first()->amount,
                "{$typed} should store as {$expected} minor units");
        }
    }

    /** The label people read is derived from the amount, so it cannot drift. */
    public function test_the_price_label_follows_the_amount(): void
    {
        $plan = new PlanModel(['amount' => 1200, 'currency' => 'gbp', 'interval' => 'month']);
        $this->assertSame('£12 / month', $plan->priceLabel());

        $plan = new PlanModel(['amount' => 999, 'currency' => 'usd', 'interval' => 'year']);
        $this->assertSame('$9.99 / year', $plan->priceLabel());

        // No amount: the manual label still works for a plan priced by hand.
        $plan = new PlanModel(['display' => 'Pay what you like']);
        $this->assertSame('Pay what you like', $plan->priceLabel());
    }

    /* ── when Stripe is and is not called ────────────────────────────────── */

    public function test_the_free_plan_is_never_sent_to_stripe(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_test_whatever');

        $free = PlanModel::where('key', 'free')->first();

        // No exception, no call — it returns immediately.
        $this->assertNull(StripeSync::plan($free));
    }

    public function test_a_plan_with_no_amount_is_never_sent_to_stripe(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_test_whatever');

        $plan = PlanModel::where('key', 'monthly')->first();
        $plan->forceFill(['amount' => null])->save();

        $this->assertNull(StripeSync::plan($plan));
    }

    /** A mode mismatch is caught before anything is sent. */
    public function test_a_live_key_in_test_mode_stops_before_calling_stripe(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_live_wrong_box');

        $plan = PlanModel::where('key', 'monthly')->first();
        $plan->forceFill(['amount' => 1200, 'currency' => 'usd', 'interval' => 'month'])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not look like a test key/');

        StripeSync::plan($plan);
    }

    /**
     * The edit must survive Stripe being unreachable.
     *
     * Saving locally first and calling Stripe second is what stops somebody's
     * typing being lost to someone else's outage.
     */
    public function test_the_plan_is_saved_even_when_stripe_refuses(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', null);   // no key at all

        $this->admin();

        $this->post(route('admin.plans.store'), [
            'key' => 'survives', 'label' => 'Survives', 'interval' => 'month',
            'amount_major' => '20.00', 'quotas' => ['story' => 5], 'is_active' => '1',
        ])->assertRedirect()->assertSessionHasErrors('stripe');

        $plan = PlanModel::where('key', 'survives')->first();

        $this->assertNotNull($plan, 'the plan must exist even though Stripe could not be reached');
        $this->assertSame(2000, $plan->amount);
    }

    /** Editing an amount keeps the plan usable in the meantime. */
    public function test_changing_an_amount_updates_the_row_immediately(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', null);

        $this->admin();
        $plan = PlanModel::where('key', 'monthly')->first();

        $this->put(route('admin.plans.update', $plan), [
            'key' => 'monthly', 'label' => 'Escalate', 'interval' => 'month',
            'amount_major' => '15.00', 'quotas' => ['story' => 5], 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(1500, $plan->fresh()->amount);
    }
}
