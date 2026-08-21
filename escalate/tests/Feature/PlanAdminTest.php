<?php

namespace Tests\Feature;

use App\Models\Plan as PlanModel;
use App\Models\User;
use App\Support\Plan;
use App\Support\Quota;
use App\Support\Settings;
use App\Support\Stripe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Plans as an administrator edits them, and Stripe's two worlds.
 *
 * The test/live split is the part worth the most tests. Stripe keeps entirely
 * separate accounts, and a price id minted in one does not exist in the other —
 * so a single price column would mean flipping the mode silently pointed
 * checkout at ids the active keys cannot resolve, failing in front of a
 * customer rather than in a deploy.
 */
class PlanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('planadmin@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── the seed ────────────────────────────────────────────────────────── */

    public function test_the_config_plans_were_carried_into_the_table(): void
    {
        $this->assertSame(
            array_keys(config('escalate.plans')),
            PlanModel::orderBy('position')->pluck('key')->all(),
        );
    }

    /* ── test vs live ────────────────────────────────────────────────────── */

    /** The active mode decides which price id is used. */
    public function test_the_mode_selects_which_price_id_is_used(): void
    {
        PlanModel::where('key', 'monthly')->update([
            'stripe_price'      => 'price_LIVE',
            'stripe_price_test' => 'price_TEST',
        ]);

        Config::set('escalate.billing.enabled', true);

        Config::set('escalate.stripe.mode', Stripe::LIVE);
        Plan::flush();
        $this->assertSame('price_LIVE', Plan::all()['monthly']['price']);

        Config::set('escalate.stripe.mode', Stripe::TEST);
        Plan::flush();
        $this->assertSame('price_TEST', Plan::all()['monthly']['price']);
    }

    /**
     * A plan priced only for live is not offered in test mode.
     *
     * Offering it would send someone to Checkout with an id the test keys
     * cannot resolve — a Stripe error, at the worst possible moment.
     */
    public function test_a_plan_priced_only_for_live_is_hidden_in_test_mode(): void
    {
        Config::set('escalate.billing.enabled', true);

        PlanModel::query()->update(['stripe_price' => null, 'stripe_price_test' => null]);
        PlanModel::where('key', 'monthly')->update(['stripe_price' => 'price_LIVE']);

        Config::set('escalate.stripe.mode', Stripe::LIVE);
        Plan::flush();
        $this->assertArrayHasKey('monthly', Plan::purchasable());

        Config::set('escalate.stripe.mode', Stripe::TEST);
        Plan::flush();
        $this->assertSame([], Plan::purchasable());
    }

    /** The mode swaps the whole credential set into the keys Cashier reads. */
    public function test_the_mode_swaps_the_credentials_cashier_uses(): void
    {
        Settings::put('escalate.stripe.live.key', 'pk_live_aaa');
        Settings::put('escalate.stripe.live.secret', 'sk_live_aaa');
        Settings::put('escalate.stripe.live.webhook_secret', 'whsec_live');
        Settings::put('escalate.stripe.test.key', 'pk_test_bbb');
        Settings::put('escalate.stripe.test.secret', 'sk_test_bbb');
        Settings::put('escalate.stripe.test.webhook_secret', 'whsec_test');

        Settings::put('escalate.stripe.mode', 'live');
        Settings::apply();
        $this->assertSame('sk_live_aaa', config('cashier.secret'));
        $this->assertSame('whsec_live', config('cashier.webhook.secret'));
        $this->assertFalse(Stripe::isTest());

        Settings::put('escalate.stripe.mode', 'test');
        Settings::apply();
        $this->assertSame('sk_test_bbb', config('cashier.secret'));
        $this->assertSame('whsec_test', config('cashier.webhook.secret'));
        $this->assertTrue(Stripe::isTest());
    }

    /** The toggle is a checkbox but must store the word, not a boolean. */
    public function test_the_mode_toggle_stores_a_mode_not_a_boolean(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), [
            'settings' => ['escalate__stripe__mode' => '1'],
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();
        $this->assertSame('test', config('escalate.stripe.mode'));

        // Absent checkbox means live, not "0".
        $this->put(route('admin.settings.update'), ['settings' => []])->assertRedirect();

        Settings::flush();
        Settings::apply();
        $this->assertSame('live', config('escalate.stripe.mode'));
    }

    /* ── editing ─────────────────────────────────────────────────────────── */

    public function test_an_admin_can_create_a_plan_and_it_takes_effect(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        $this->admin();

        $this->post(route('admin.plans.store'), [
            'key' => 'founder', 'label' => 'Founder', 'display' => '$30 / month',
            'interval' => 'month', 'stripe_price' => 'price_founder',
            'quotas' => ['story' => 20, 'narration' => 20, 'rewind' => 10],
            'is_active' => '1', 'position' => 5,
        ])->assertRedirect(route('admin.plans'));

        Plan::flush();

        $this->assertArrayHasKey('founder', Plan::purchasable());

        $person = $this->makeUser('founder@escalate.test');
        $person->forceFill(['plan_override' => 'founder'])->save();

        $this->assertSame(20, Quota::limit($person->fresh(), 'story'));
    }

    public function test_editing_a_plans_quota_changes_what_people_on_it_get(): void
    {
        Config::set('escalate.billing.enabled', true);

        $person = $this->makeUser('onmonthly@escalate.test');
        $person->forceFill(['plan_override' => 'monthly'])->save();

        $this->assertSame(5, Quota::limit($person->fresh(), 'story'));

        $this->admin();
        $plan = PlanModel::where('key', 'monthly')->first();

        $this->put(route('admin.plans.update', $plan), [
            'key' => 'monthly', 'label' => 'Escalate', 'interval' => 'month',
            'quotas' => ['story' => 9, 'narration' => 9, 'rewind' => 9],
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(9, Quota::limit($person->fresh(), 'story'));
    }

    public function test_a_price_id_must_look_like_a_price_id(): void
    {
        $this->admin();

        $this->post(route('admin.plans.store'), [
            'key' => 'oops', 'label' => 'Oops',
            'stripe_price' => 'prod_notaprice',
            'quotas' => ['story' => 1],
        ])->assertSessionHasErrors('stripe_price');

        $this->assertNull(PlanModel::where('key', 'oops')->first());
    }

    public function test_a_key_must_be_url_safe_and_unique(): void
    {
        $this->admin();

        $this->post(route('admin.plans.store'), ['key' => 'Not A Key', 'label' => 'X'])
            ->assertSessionHasErrors('key');

        $this->post(route('admin.plans.store'), ['key' => 'monthly', 'label' => 'Clash'])
            ->assertSessionHasErrors('key');
    }

    /* ── the guards ──────────────────────────────────────────────────────── */

    /** Free is what everyone falls back to; without it nothing resolves. */
    public function test_the_free_plan_cannot_be_deleted(): void
    {
        $this->admin();
        $free = PlanModel::where('key', 'free')->first();

        $this->delete(route('admin.plans.destroy', $free))->assertSessionHasErrors('plan');

        $this->assertNotNull($free->fresh());
    }

    public function test_the_free_plan_cannot_be_renamed_or_switched_off(): void
    {
        $this->admin();
        $free = PlanModel::where('key', 'free')->first();

        $this->put(route('admin.plans.update', $free), [
            'key' => 'not-free', 'label' => 'Free', 'quotas' => ['story' => 1],
        ])->assertRedirect();

        $free->refresh();
        $this->assertSame('free', $free->key);
        $this->assertTrue($free->is_active);
    }

    /**
     * A plan with people on it is deactivated, never deleted — otherwise their
     * subscriptions point at a price the app no longer knows while Stripe
     * carries on charging.
     */
    public function test_a_plan_with_people_on_it_cannot_be_deleted(): void
    {
        $person = $this->makeUser('subscriber@escalate.test');
        $person->forceFill(['plan_override' => 'monthly'])->save();

        $this->admin();
        $plan = PlanModel::where('key', 'monthly')->first();

        $this->delete(route('admin.plans.destroy', $plan))->assertSessionHasErrors('plan');

        $this->assertNotNull($plan->fresh());
    }

    public function test_an_empty_plan_can_be_deleted(): void
    {
        $this->admin();
        $plan = PlanModel::where('key', 'yearly')->first();

        $this->delete(route('admin.plans.destroy', $plan))->assertRedirect(route('admin.plans'));

        $this->assertNull($plan->fresh());
    }

    /** Deactivating hides it from the picker but keeps existing people on it. */
    public function test_a_deactivated_plan_is_hidden_but_still_honoured(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.stripe.mode', Stripe::LIVE);

        PlanModel::where('key', 'monthly')->update([
            'stripe_price' => 'price_LIVE', 'is_active' => false,
        ]);
        Plan::flush();

        $this->assertArrayNotHasKey('monthly', Plan::purchasable());

        $person = $this->makeUser('grandfathered@escalate.test');
        $person->forceFill(['plan_override' => 'monthly'])->save();

        $this->assertSame('monthly', Plan::for($person->fresh()));
        $this->assertSame(5, Quota::limit($person->fresh(), 'story'));
    }

    /* ── access ──────────────────────────────────────────────────────────── */

    public function test_the_plan_screens_are_invisible_to_an_ordinary_user(): void
    {
        $user = $this->makeUser('nosy@escalate.test');
        $plan = PlanModel::where('key', 'monthly')->first();

        foreach ([
            ['get', route('admin.plans')],
            ['get', route('admin.plans.create')],
            ['post', route('admin.plans.store')],
            ['get', route('admin.plans.edit', $plan)],
            ['put', route('admin.plans.update', $plan)],
            ['delete', route('admin.plans.destroy', $plan)],
        ] as [$verb, $url]) {
            $this->actingAs($user)->{$verb}($url)->assertNotFound();
        }
    }
}
