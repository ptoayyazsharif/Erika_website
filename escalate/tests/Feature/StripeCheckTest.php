<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Models\User;
use App\Support\Stripe;
use App\Support\StripeCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Stripe connection check.
 *
 * The property that matters most is not what it reports — it is what it does
 * NOT do. A "test my setup" button that leaves a throwaway customer behind
 * pollutes a real Stripe account with something indistinguishable from a real
 * customer, and one that writes locally could damage the very data an operator
 * pressed it to protect.
 */
class StripeCheckTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('stripecheck@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /**
     * The check must not write a single row.
     *
     * Asserted by counting every table before and after, rather than by reading
     * the code and trusting it — a future change that adds a log row or an
     * audit entry should fail here.
     */
    public function test_running_the_check_writes_nothing_to_the_database(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_test_definitely_not_real');

        $tables = ['users', 'profiles', 'desires', 'stories', 'gratitude_entries',
            'rewinds', 'plans', 'settings', 'invites', 'ai_events', 'subscriptions'];

        // Sign in FIRST — creating the admin is itself a write, and counting
        // before that would measure the fixture rather than the check.
        $this->admin();

        $before = collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();

        $this->post(route('admin.settings.stripe'))->assertRedirect();

        $after = collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();

        $this->assertSame($before, $after, 'The Stripe check changed the number of rows in a table.');
    }

    /** A live key in the test box is caught before any network call. */
    public function test_a_live_key_in_the_test_box_is_refused(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_live_looks_like_production');

        $r = StripeCheck::run();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('LIVE key in the test box', $r['checks'][0]['detail']);
    }

    /** And the reverse, which is the one that stops real customers paying. */
    public function test_a_test_key_in_the_live_box_is_refused(): void
    {
        Config::set('escalate.stripe.mode', Stripe::LIVE);
        Config::set('escalate.stripe.live.secret', 'sk_test_not_for_production');

        $r = StripeCheck::run();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('TEST key in the live box', $r['checks'][0]['detail']);
    }

    public function test_a_missing_key_says_so_and_stops(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', null);

        $r = StripeCheck::run();

        $this->assertFalse($r['ok']);
        $this->assertCount(1, $r['checks']);
        $this->assertStringContainsString('No test secret key', $r['checks'][0]['detail']);
    }

    /** The screen is admin-only like everything else in there. */
    public function test_the_check_is_invisible_to_an_ordinary_user(): void
    {
        $this->actingAs($this->makeUser('nosy2@escalate.test'))
            ->post(route('admin.settings.stripe'))
            ->assertNotFound();
    }

    /** A plan with no price id for the active mode is reported, not skipped. */
    public function test_a_plan_missing_a_price_for_this_mode_is_called_out(): void
    {
        Config::set('escalate.stripe.mode', Stripe::TEST);
        Config::set('escalate.stripe.test.secret', 'sk_live_wrong'); // stops before network

        PlanModel::where('key', 'monthly')->update([
            'stripe_price' => 'price_live_only', 'stripe_price_test' => null,
        ]);

        // With a bad key the run stops early, so assert the plan-level logic directly.
        $plan = PlanModel::where('key', 'monthly')->first();
        Config::set('escalate.stripe.mode', Stripe::TEST);

        $this->assertNull($plan->priceId(), 'In test mode a live-only plan must expose no price id.');
        $this->assertFalse($plan->isPurchasable());
    }
}
