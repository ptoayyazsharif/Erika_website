<?php

namespace Tests\Feature;

use App\Jobs\WriteRewind;
use App\Models\Rewind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Rewinds — the looking-back half of the app.
 *
 * The desire screen had been offering a "Begin a Rewind" button that pointed
 * at a placeholder, so finishing the thing the whole app is about and pressing
 * the one button offered landed you on "not written yet".
 */
class RewindTest extends TestCase
{
    use RefreshDatabase;

    private function completedDesire($user)
    {
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $desire->moveTo('manifested');

        return $desire->fresh();
    }

    public function test_a_finished_desire_can_be_looked_back_at(): void
    {
        $user = $this->makeUser('rewind@escalate.test');
        $desire = $this->completedDesire($user);

        $this->actingAs($user)->get(route('rewinds.create', $desire))
            ->assertOk()
            ->assertSee('What happened?');
    }

    /**
     * A Rewind is a looking-back, so there must be something to look back at.
     * Offering one mid-flight asks someone to write the ending of a story they
     * are still inside.
     */
    public function test_a_desire_still_in_motion_has_no_rewind(): void
    {
        $user = $this->makeUser('early@escalate.test');
        $desire = $user->desires()->create(['title' => 'Still going', 'status' => 'desired']);

        $this->actingAs($user)->get(route('rewinds.create', $desire))->assertNotFound();
        $this->actingAs($user)->post(route('rewinds.store', $desire), ['what_happened' => 'x'])->assertNotFound();
    }

    public function test_answers_are_saved_and_encrypted(): void
    {
        $user = $this->makeUser('answers@escalate.test');
        $desire = $this->completedDesire($user);

        $this->actingAs($user)->post(route('rewinds.store', $desire), [
            'what_happened' => 'The lease came through in March.',
            'people'        => 'Marta drove the van.',
        ])->assertRedirect();

        $rewind = $user->fresh()->rewinds()->first();

        $this->assertSame('The lease came through in March.', $rewind->what_happened);
        $this->assertCount(2, $rewind->answered());
        $this->assertStringNotContainsString('Marta', $rewind->getRawOriginal('people'));
    }

    public function test_writing_it_up_is_queued_and_never_invents_from_nothing(): void
    {
        Queue::fake();

        $user = $this->makeUser('write@escalate.test');
        $desire = $this->completedDesire($user);

        // Empty answers: there is nothing to write from, and the prompt forbids
        // inventing — so this must not spend a generation.
        $empty = new Rewind;
        $empty->forceFill(['user_id' => $user->id, 'desire_id' => $desire->id, 'state' => 'draft'])->save();

        $this->actingAs($user)->post(route('rewinds.generate', $empty))->assertRedirect();
        Queue::assertNothingPushed();

        $empty->fill(['what_happened' => 'The lease came through in March.'])->save();

        $this->actingAs($user)->post(route('rewinds.generate', $empty))->assertRedirect();
        Queue::assertPushed(WriteRewind::class);
        $this->assertSame('queued', $empty->fresh()->state);
    }

    public function test_a_stranger_cannot_read_or_touch_another_rewind(): void
    {
        $owner = $this->makeUser('owner@escalate.test');
        $stranger = $this->makeUser('stranger@escalate.test');
        $desire = $this->completedDesire($owner);

        $rewind = new Rewind;
        $rewind->forceFill([
            'user_id' => $owner->id, 'desire_id' => $desire->id,
            'state' => 'draft', 'what_happened' => 'Private',
        ])->save();

        // 404 rather than 403 — a 403 would confirm the row exists.
        $this->actingAs($stranger)->get(route('rewinds.show', $rewind))->assertNotFound();
        $this->actingAs($stranger)->post(route('rewinds.generate', $rewind))->assertNotFound();
        $this->actingAs($stranger)->delete(route('rewinds.destroy', $rewind))->assertNotFound();
        $this->actingAs($stranger)->get(route('rewinds.create', $desire))->assertNotFound();

        $this->assertNotNull($rewind->fresh());
    }

    public function test_the_index_offers_finished_desires_that_have_no_rewind_yet(): void
    {
        $user = $this->makeUser('index@escalate.test');
        $this->completedDesire($user);

        $this->actingAs($user)->get(route('rewinds.index'))
            ->assertOk()
            ->assertSee('Ready to look back at')
            ->assertSee('A quiet house');
    }

    public function test_a_failed_write_never_destroys_the_answers(): void
    {
        $user = $this->makeUser('failed@escalate.test');
        $desire = $this->completedDesire($user);

        $rewind = new Rewind;
        $rewind->forceFill([
            'user_id' => $user->id, 'desire_id' => $desire->id,
            'state' => 'writing', 'what_happened' => 'The lease came through in March.',
        ])->save();

        (new WriteRewind($rewind))->failed(new \RuntimeException('provider down'));

        $rewind->refresh();

        $this->assertSame('failed', $rewind->state);
        $this->assertSame('The lease came through in March.', $rewind->what_happened);
    }
}
