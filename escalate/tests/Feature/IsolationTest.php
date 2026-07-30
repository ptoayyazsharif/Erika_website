<?php

namespace Tests\Feature;

use App\Models\Desire;
use App\Models\Narration;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * One user must never reach another user's anything.
 *
 * This is the test that matters most in the whole suite. Every route that takes
 * a model id is exercised twice — once as the owner, once as a stranger — and
 * the stranger must get a 404 rather than a 403, because "403" confirms the row
 * exists and membership of a private journal is itself sensitive.
 */
class IsolationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return $this->makeUser($email);
    }

    public function test_a_stranger_cannot_read_another_users_desire(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $desire = $owner->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $this->actingAs($owner)->get(route('desires.show', $desire))->assertOk();
        $this->actingAs($stranger)->get(route('desires.show', $desire))->assertNotFound();
    }

    public function test_a_stranger_cannot_change_another_users_desire(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');
        $desire = $owner->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $this->actingAs($stranger)
            ->patch(route('desires.status', $desire), ['status' => 'released'])
            ->assertNotFound();

        $this->actingAs($stranger)
            ->delete(route('desires.destroy', $desire))
            ->assertNotFound();

        $this->assertDatabaseHas('desires', ['id' => $desire->id, 'status' => 'desired']);
    }

    public function test_a_stranger_cannot_read_another_users_story_or_its_state(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $story = $this->makeReadyStory($owner);

        $this->actingAs($owner)->get(route('stories.show', $story))->assertOk();
        $this->actingAs($stranger)->get(route('stories.show', $story))->assertNotFound();
        $this->actingAs($stranger)->getJson(route('stories.state', $story))->assertNotFound();
    }

    public function test_a_stranger_cannot_spend_another_users_quota(): void
    {
        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');
        $desire = $owner->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $this->makeReadyStory($owner);

        $this->actingAs($stranger)->post(route('stories.store', $desire))->assertNotFound();
        $this->actingAs($stranger)->post(route('stories.narrate', $story))->assertNotFound();
        $this->actingAs($stranger)->post(route('stories.regenerate', $story))->assertNotFound();

        $this->assertSame(0, \App\Models\AiEvent::count());
    }

    public function test_narration_audio_is_only_served_to_its_owner(): void
    {
        Storage::fake('private');

        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $story = $this->makeReadyStory($owner);
        $narration = $this->makeReadyNarration($story);

        $this->actingAs($owner)->get(route('media.narration', $narration))->assertOk();
        $this->actingAs($stranger)->get(route('media.narration', $narration))->assertNotFound();
        // The guest case lives in test_every_protected_route_rejects_a_guest:
        // actingAs() persists for the rest of a test, so an unauthenticated
        // assertion here would silently still be the stranger.
    }

    /**
     * The routes the earlier tests missed.
     *
     * All eight were verified correct by reading, but nothing in the suite kept
     * them that way — and these are exactly the actions where a missing
     * ownership check is invisible, because they redirect rather than render.
     */
    public function test_no_write_route_can_be_reached_across_accounts(): void
    {
        Storage::fake('private');

        $owner = $this->user('owner@escalate.test');
        $stranger = $this->user('stranger@escalate.test');

        $desire = $owner->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $this->makeReadyStory($owner);
        // forceFill because `path` is deliberately not mass-assignable.
        $image = $owner->desireImages()->make(['role' => 'vision']);
        $image->forceFill(['desire_id' => $desire->id, 'path' => 'images/x.png'])->save();

        $as = fn () => $this->actingAs($stranger);

        $as()->get(route('desires.edit', $desire))->assertNotFound();
        $as()->put(route('desires.update', $desire), ['title' => 'hijacked'])->assertNotFound();
        $as()->get(route('stories.edit', $story))->assertNotFound();
        $as()->put(route('stories.update', $story), ['edited_body' => str_repeat('word ', 40)])->assertNotFound();
        $as()->post(route('stories.favourite', $story))->assertNotFound();
        $as()->post(route('stories.played', $story))->assertNotFound();
        $as()->delete(route('stories.destroy', $story))->assertNotFound();
        $as()->get(route('media.image', $image))->assertNotFound();

        // Nothing changed hands.
        $this->assertSame('A quiet house', $desire->fresh()->title);
        $this->assertFalse($story->fresh()->favourite);
        $this->assertSame(0, $story->fresh()->play_count);
        $this->assertNull($story->fresh()->edited_body);
        $this->assertNotNull($story->fresh());
    }

    public function test_a_suspended_account_loses_access_mid_session(): void
    {
        $user = $this->user('suspended@escalate.test');

        // Signed in and working.
        $this->actingAs($user)->get(route('desires.index'))->assertOk();

        // Suspended by an admin while the session is still live. Checking only
        // at login would leave this account fully operational until it lapsed.
        $user->forceFill(['suspended_at' => now()])->save();

        $this->actingAs($user)->get(route('desires.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_every_protected_route_rejects_a_guest(): void
    {
        Storage::fake('private');

        $owner = $this->user('owner@escalate.test');
        $desire = $owner->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $this->makeReadyStory($owner);
        $narration = $this->makeReadyNarration($story);

        foreach ([
            route('media.narration', $narration),
            route('today'),
            route('world.edit'),
            route('desires.index'),
            route('desires.create'),
            route('desires.show', $desire),
            route('stories.index'),
            route('stories.show', $story),
            route('stories.state', $story),
            route('journey'),
            route('gratitude.index'),
            route('rewinds.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }
}
