<?php

namespace Tests\Feature;

use App\Jobs\WriteAffirmations;
use App\Models\AffirmationSet;
use App\Models\User;
use App\Services\AffirmationWriter;
use App\Support\Ceiling;
use App\Support\Plan;
use App\Support\Quota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Daily affirmation cards.
 *
 * Promised in the launch materials and, until now, only two empty tables. The
 * plumbing they need already half-existed and half-disagreed with itself: the
 * quota key was the only plural in a list of singulars, so
 * Quota::limit('affirmation') answered zero and the feature could never have
 * run even once it was written.
 */
class AffirmationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A set, forced rather than created.
     *
     * user_id and state are deliberately absent from AffirmationSet::$fillable
     * — state especially, so a client cannot post state=ready — so the fixture
     * has to write them the same way the application does.
     */
    private function makeSet(array $attributes): AffirmationSet
    {
        $set = new AffirmationSet;
        $set->forceFill($attributes)->save();

        return $set;
    }

    /** Same reason as makeSet(): user_id on a card is server-derived. */
    private function makeCard(AffirmationSet $set, array $attributes): \App\Models\Affirmation
    {
        $card = new \App\Models\Affirmation;
        $card->forceFill(array_merge([
            'affirmation_set_id' => $set->id,
            'user_id'            => $set->user_id,
            'position'           => 0,
        ], $attributes))->save();

        return $card;
    }

    private function person(string $email = 'cards@escalate.test'): User
    {
        // makeUser() already gives them a profile; world() reads it.
        $user = $this->makeUser($email);
        $user->desires()->create(['title' => 'A quiet morning']);

        return $user->fresh();
    }

    /* ── the plumbing that was wrong ─────────────────────────────────────── */

    /** The bug that would have made the whole feature a no-op. */
    public function test_the_daily_allowance_is_a_real_number_not_zero(): void
    {
        Config::set('escalate.quotas.affirmations_per_day', 2);
        Config::set('escalate.billing.enabled', false);

        $this->assertSame(2, Quota::limit($this->person(), 'affirmation'));
    }

    /** With billing on, the allowance comes from the plan and is still not zero. */
    public function test_a_plan_grants_affirmations_once_billing_is_on(): void
    {
        Config::set('escalate.billing.enabled', true);

        $this->assertGreaterThan(0, Plan::config('free')['quotas']['affirmation'] ?? 0);
        $this->assertGreaterThan(0, Plan::config('monthly')['quotas']['affirmation'] ?? 0);
    }

    /** The whole-application ceiling knows about them too. */
    public function test_the_ceiling_covers_affirmations(): void
    {
        Config::set('escalate.ceiling.affirmations_per_day', 1);

        $user = $this->person();
        $this->makeSet(['user_id' => $user->id, 'for_date' => today(), 'state' => 'queued']);

        $this->assertFalse(Ceiling::allows('affirmation'));
    }

    /* ── drawing ─────────────────────────────────────────────────────────── */

    public function test_drawing_queues_one_set_for_today(): void
    {
        Queue::fake();
        Config::set('escalate.quotas.affirmations_per_day', 2);

        $user = $this->person();

        $this->actingAs($user)->post(route('affirmations.store'))->assertRedirect(route('affirmations'));

        $set = $user->affirmationSets()->first();

        $this->assertNotNull($set);
        $this->assertTrue($set->for_date->isToday());
        $this->assertSame('queued', $set->state);

        Queue::assertPushed(WriteAffirmations::class);
    }

    /**
     * Two taps must not buy two sets.
     *
     * A unique index on (user_id, for_date) settles the race rather than a
     * check-then-write, which two taps a second apart on a phone both pass.
     */
    public function test_asking_twice_in_one_day_does_not_pay_twice(): void
    {
        Queue::fake();
        Config::set('escalate.quotas.affirmations_per_day', 5);

        $user = $this->person();

        $this->actingAs($user)->post(route('affirmations.store'));
        $this->actingAs($user)->post(route('affirmations.store'));

        $this->assertSame(1, $user->affirmationSets()->count());
        Queue::assertPushed(WriteAffirmations::class, 1);
    }

    public function test_running_out_says_so_and_queues_nothing(): void
    {
        Queue::fake();
        Config::set('escalate.billing.enabled', false);
        Config::set('escalate.quotas.affirmations_per_day', 0);

        $this->actingAs($this->person())->post(route('affirmations.store'))->assertRedirect();

        Queue::assertNothingPushed();
        $this->assertSame(0, AffirmationSet::count());
    }

    /* ── reading ─────────────────────────────────────────────────────────── */

    public function test_the_screen_shows_todays_cards(): void
    {
        $user = $this->person();
        $set = $this->makeSet(['user_id' => $user->id, 'for_date' => today(), 'state' => 'ready']);

        $this->makeCard($set, [
            'user_id' => $user->id, 'body' => 'I keep the mornings for myself.',
            'back' => 'You already wake before the house does.', 'position' => 0,
        ]);

        $this->actingAs($user)->get(route('affirmations'))
            ->assertOk()
            ->assertSee('I keep the mornings for myself.')
            ->assertSee('You already wake before the house does.');
    }

    /** One person's cards are another person's 404. */
    public function test_a_card_belonging_to_someone_else_cannot_be_kept(): void
    {
        $mine = $this->person('mine@escalate.test');
        $theirs = $this->person('theirs@escalate.test');

        $set = $this->makeSet(['user_id' => $theirs->id, 'for_date' => today(), 'state' => 'ready']);
        $card = $this->makeCard($set, [
            'user_id' => $theirs->id, 'body' => 'Private to them.', 'position' => 0,
        ]);

        $this->actingAs($mine)->post(route('affirmations.favourite', $card))->assertNotFound();

        $this->assertFalse($card->fresh()->favourite);
    }

    public function test_keeping_a_card_toggles_it(): void
    {
        $user = $this->person();
        $set = $this->makeSet(['user_id' => $user->id, 'for_date' => today(), 'state' => 'ready']);
        $card = $this->makeCard($set, ['user_id' => $user->id, 'body' => 'Mine.', 'position' => 0]);

        $this->actingAs($user)->post(route('affirmations.favourite', $card));
        $this->assertTrue($card->fresh()->favourite);

        $this->actingAs($user)->post(route('affirmations.favourite', $card));
        $this->assertFalse($card->fresh()->favourite);
    }

    /** The poll endpoint says only what the screen needs. */
    public function test_the_state_endpoint_leaks_nothing_about_the_provider(): void
    {
        $user = $this->person();
        $set = $this->makeSet([
            'user_id' => $user->id, 'for_date' => today(), 'state' => 'ready', 'model' => 'claude-sonnet-5',
        ]);
        $this->makeCard($set, ['user_id' => $user->id, 'body' => 'A card.', 'position' => 0]);

        $response = $this->actingAs($user)->get(route('affirmations.state'))->assertOk();

        $response->assertJsonPath('state', 'ready');
        $response->assertSee('A card.');
        $response->assertDontSee('claude');
        $response->assertDontSee('anthropic', false);
    }

    /* ── the writer ──────────────────────────────────────────────────────── */

    /**
     * A desire id the model invented must not attach to a card.
     *
     * affirmations.desire_id has a foreign key but no ownership constraint, so
     * an id belonging to somebody else would save cleanly and quietly link one
     * person's card to another person's desire.
     */
    public function test_a_desire_id_we_did_not_supply_is_discarded(): void
    {
        $mine = $this->person('writer@escalate.test');
        $stranger = $this->person('stranger@escalate.test');
        $theirDesire = $stranger->desires()->first();

        $set = $this->makeSet(['user_id' => $mine->id, 'for_date' => today(), 'state' => 'writing']);

        $this->mock(\App\Services\Ai\Anthropic::class, function ($mock) use ($theirDesire) {
            $mock->shouldReceive('write')->andReturn(
                "FRONT: I keep the mornings for myself.\n".
                "BACK: You already wake before the house does. (DESIRE {$theirDesire->id})"
            );
        });

        app(AffirmationWriter::class)->write($set->fresh());

        $card = $set->fresh()->affirmations->first();

        $this->assertNotNull($card);
        $this->assertNull($card->desire_id, 'A desire id from another account was accepted.');
        $this->assertSame('I keep the mornings for myself.', $card->body);

        // The routing tag is an instruction, not part of the sentence.
        $this->assertSame('You already wake before the house does.', $card->back);
    }

    /** Cards are journal content, so they are stored like journal content. */
    public function test_cards_are_encrypted_at_rest(): void
    {
        $user = $this->person();
        $set = $this->makeSet(['user_id' => $user->id, 'for_date' => today(), 'state' => 'ready']);
        $this->makeCard($set, [
            'user_id' => $user->id, 'body' => 'A very distinctive sentence.', 'position' => 0,
        ]);

        $raw = \DB::table('affirmations')->where('user_id', $user->id)->first();

        $this->assertStringNotContainsString('distinctive', $raw->body);
    }
}
