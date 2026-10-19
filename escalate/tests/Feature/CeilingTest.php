<?php

namespace Tests\Feature;

use App\Jobs\WriteStory;
use App\Models\AiEvent;
use App\Support\Ceiling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The whole-application ceiling.
 *
 * The per-user quota is an allowance and it multiplies by the number of
 * accounts, so it is the wrong shape of limit for the failure that actually
 * costs money: not one greedy person, but a hundred accounts that should not
 * exist. This is the limit that does not multiply.
 */
class CeilingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('escalate.beta.require_verification', false);
        Config::set('escalate.beta.invite_only', false);
    }

    /** Successful generations by anyone, in the last day. */
    private function spend(string $kind, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            AiEvent::create([
                'kind' => $kind, 'provider' => 'anthropic', 'ok' => true, 'user_id' => null,
            ]);
        }
    }

    public function test_one_persons_request_is_refused_once_everyone_else_has_spent_the_day(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 5);
        Queue::fake();

        // A user well inside their own quota — this is not their limit.
        $user = $this->makeUser('inside-quota@escalate.test');
        $desire = $user->desires()->create(['title' => 'A quiet house']);

        $this->spend('story', 5);

        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect();

        Queue::assertNothingPushed();
        $this->assertSame(0, $user->stories()->count());
        $this->assertStringContainsString('across everyone using it', session('status'));
    }

    public function test_below_the_ceiling_nothing_changes(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 5);
        Queue::fake();

        $user = $this->makeUser('under@escalate.test');
        $desire = $user->desires()->create(['title' => 'A quiet house']);

        $this->spend('story', 4);

        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect();

        Queue::assertPushed(WriteStory::class);
        $this->assertSame(1, $user->stories()->count());
    }

    /**
     * Re-checked at the point of spending, not just at the point of asking —
     * the same check-then-spend window the per-user quota closes. Many
     * requests can pass the controller before any of them reaches a worker.
     */
    public function test_the_job_refuses_to_spend_when_the_ceiling_moved_under_it(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 5);

        // WriteStory bails before the ceiling check when no provider is
        // configured, which is the state of the test environment — so give it
        // a key it will never actually use.
        Config::set('escalate.anthropic.key', 'test-key');

        $user = $this->makeUser('queued@escalate.test');
        $story = $user->stories()->make();
        $story->forceFill(['state' => 'queued'])->save();

        // The rest of the world used the day up while this sat in the queue.
        $this->spend('story', 5);

        (new WriteStory($story))->handle(
            app(\App\Services\StoryWriter::class),
            app(\App\Services\Ai\Anthropic::class),
        );

        $story->refresh();
        $this->assertSame('failed', $story->state);
        $this->assertStringContainsString('across everyone using it', $story->failure_reason);
    }

    /** Queued work counts before it lands, or the window never closes. */
    public function test_work_already_in_flight_counts_against_the_ceiling(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 3);

        $other = $this->makeUser('elsewhere@escalate.test');

        foreach (range(1, 3) as $i) {
            $story = $other->stories()->make();
            $story->forceFill(['state' => 'queued'])->save();
        }

        $this->assertSame(3, Ceiling::used('story'));
        $this->assertFalse(Ceiling::allows('story'));
    }

    /**
     * Zero means unlimited, not blocked.
     *
     * The other reading turns an unset environment variable into a silently
     * dead app whose symptom is every generation failing with a message about
     * a limit nobody configured.
     */
    public function test_a_ceiling_of_zero_is_unlimited(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 0);

        $this->spend('story', 500);

        $this->assertTrue(Ceiling::allows('story'));
    }

    /** A provider outage must not lock everyone out of an already-broken app. */
    public function test_failed_calls_do_not_consume_the_ceiling(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 5);

        for ($i = 0; $i < 20; $i++) {
            AiEvent::create([
                'kind' => 'story', 'provider' => 'anthropic', 'ok' => false,
                'error_code' => 'transport', 'user_id' => null,
            ]);
        }

        $this->assertTrue(Ceiling::allows('story'));
    }

    public function test_yesterdays_spending_does_not_count_against_today(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 5);

        $this->spend('story', 5);
        AiEvent::query()->update(['created_at' => now()->subDays(2)]);

        $this->assertTrue(Ceiling::allows('story'));
    }

    /** Each kind has its own ceiling; running out of one leaves the others. */
    public function test_the_ceilings_are_counted_per_kind(): void
    {
        Config::set('escalate.ceiling.stories_per_day', 2);
        Config::set('escalate.ceiling.narrations_per_day', 2);

        $this->spend('story', 2);

        $this->assertFalse(Ceiling::allows('story'));
        $this->assertTrue(Ceiling::allows('narration'));
    }
}
