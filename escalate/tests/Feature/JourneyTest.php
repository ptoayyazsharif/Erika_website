<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Journey shows what actually happened, in order.
 *
 * It was a placeholder, which is the worst thing this particular screen could
 * be: the premise of the app is that naming something and watching it move is
 * worth doing, and the screen meant to show that movement said "not written
 * yet".
 */
class JourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_is_told_how_the_journey_starts(): void
    {
        $user = $this->makeUser('empty@escalate.test');

        $this->actingAs($user)->get(route('journey'))
            ->assertOk()
            ->assertSee('Your journey starts with one thing')
            ->assertSee(route('desires.create'));
    }

    public function test_naming_a_desire_and_moving_it_both_appear(): void
    {
        $user = $this->makeUser('moving@escalate.test');

        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $desire->moveTo('manifested');

        $response = $this->actingAs($user)->get(route('journey'))->assertOk();

        $response->assertSee('A quiet house');
        $response->assertSee('Named it.');
        $response->assertSee('Manifested');
    }

    /**
     * A desire that has not moved must not report a move.
     *
     * status_changed_at is set when the desire is created, so reporting it
     * unconditionally would double every row and invent a transition that
     * never happened.
     */
    public function test_an_untouched_desire_contributes_one_event_not_two(): void
    {
        $user = $this->makeUser('once@escalate.test');
        $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $body = $this->actingAs($user)->get(route('journey'))->assertOk()->getContent();
        $timeline = substr($body, strpos($body, 'class="timeline"'));

        $this->assertSame(1, substr_count($timeline, 'timeline-item'));
    }

    public function test_readings_and_gratitude_share_the_timeline(): void
    {
        $user = $this->makeUser('mixed@escalate.test');

        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $this->makeReadyStory($user, null, $desire->id);
        $user->gratitudeEntries()->create([
            'body'     => 'The light in the kitchen',
            'for_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('journey'))->assertOk();

        $response->assertSee('A quiet house');
        $response->assertSee('The light in the kitchen');
        $response->assertSee('Gratitude');
    }

    public function test_the_journey_belongs_to_one_person_only(): void
    {
        $mine = $this->makeUser('mine@escalate.test');
        $theirs = $this->makeUser('theirs@escalate.test');

        $theirs->desires()->create(['title' => 'Somebody elses house', 'status' => 'desired']);
        $mine->desires()->create(['title' => 'My own house', 'status' => 'desired']);

        $this->actingAs($mine)->get(route('journey'))
            ->assertOk()
            ->assertSee('My own house')
            ->assertDontSee('Somebody elses house');
    }
}
