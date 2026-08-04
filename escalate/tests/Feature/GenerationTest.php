<?php

namespace Tests\Feature;

use App\Jobs\NarrateStory;
use App\Jobs\WriteStory;
use App\Models\AiEvent;
use App\Models\Narration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The generation pipeline, with both providers faked.
 *
 * Faked rather than mocked: Http::fake intercepts at the transport layer, so the
 * request this app would really send is still built, signed and inspected. That
 * is what lets these tests assert on the outgoing headers and body — the parts
 * most likely to break silently.
 */
class GenerationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = $this->makeUser('mark@escalate.test', 'Mark');
        $user->profile->update(['city' => 'Atlanta']);

        return $user->refresh();
    }

    private function fakeStory(string $text): void
    {
        config(['escalate.anthropic.key' => 'sk-ant-fake']);

        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => $text]],
            'usage'   => ['input_tokens' => 900, 'output_tokens' => 700],
        ])]);
    }

    /** Real MPEG-1 Layer III frame headers, so durationMs() has something to read. */
    private function fakeAudio(): string
    {
        // 128 kbps, 44.1 kHz mono frames: 0xFF 0xFB 0x90 0x64, padded to length.
        return str_repeat("\xFF\xFB\x90\x64".str_repeat("\x00", 413), 200);
    }

    /**
     * The reader is never a character in their own reading.
     *
     * The live app shipped readings that said "Erika leaves it there. She
     * stands a second longer" — third person, about the person reading it,
     * when first person had been asked for. The cause was two instructions
     * pulling against each other: the context block announced "Call them:
     * Erika" and rule 4 required every name to appear in the prose, so the
     * model used it, and using it forces third person.
     *
     * Both halves are asserted here because fixing either one alone leaves the
     * conflict intact.
     */
    public function test_the_prompt_forbids_writing_the_readers_own_name(): void
    {
        $user = $this->user();          // named Mark
        $this->fakeStory(str_repeat('word ', 300));

        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $user->stories()->make();
        $story->forceFill(['desire_id' => $desire->id, 'state' => 'queued'])->save();

        app(\App\Services\StoryWriter::class)->write($story);

        $sent = '';
        Http::assertSent(function ($request) use (&$sent) {
            $sent = json_encode($request->data());

            return true;
        });

        $this->assertStringContainsString('never write it in the piece', $sent);
        // Substring stops short of the curly apostrophe, which json_encode
        // escapes — asserting past it tests PHP's escaping, not the prompt.
        $this->assertStringContainsString('Never write the reader', $sent);
        $this->assertStringContainsString('meaning the reader', $sent);
        // First person is the default, and it must say so unmistakably.
        $this->assertStringContainsString('First person, throughout', $sent);
        // The old phrasing is what caused it; make sure it cannot come back.
        $this->assertStringNotContainsString('Call them:', $sent);
    }

    /**
     * The voice is choosable at the moment you ask to hear it.
     *
     * It used to come only from My World — a settings screen most people never
     * open — so in practice everyone got the default whether it suited them or
     * not. The choice is validated against the configured keys, because this
     * value picks which voice id the server bills against.
     */
    public function test_a_voice_chosen_at_narration_is_used_and_remembered(): void
    {
        $user = $this->user();
        $story = $this->makeReadyStory($user);

        $this->assertSame('still', $user->world()->voice);

        $this->actingAs($user)
            ->post(route('stories.narrate', $story), ['voice' => 'storyteller'])
            ->assertRedirect();

        $this->assertDatabaseHas('narrations', [
            'story_id' => $story->id,
            'voice'    => 'storyteller',
        ]);

        // Remembered, so the next reading does not make them pick again.
        $this->assertSame('storyteller', $user->fresh()->world()->voice);
    }

    public function test_an_unknown_voice_is_rejected_rather_than_billed(): void
    {
        $user = $this->user();
        $story = $this->makeReadyStory($user);

        $this->actingAs($user)
            ->post(route('stories.narrate', $story), ['voice' => 'not-a-voice'])
            ->assertSessionHasErrors('voice');

        $this->assertSame(0, \App\Models\Narration::count());
        $this->assertSame('still', $user->fresh()->world()->voice);
    }

    public function test_a_new_desire_records_when_its_status_was_set(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('desires.store'), [
            'title' => 'A quiet house',
            'category' => 'home',
            'timeframe' => 'this_year',
            'story_length' => 'medium',
            'perspective' => 'first',
            'tone' => 'grounded',
            // A client trying to declare it already manifested must be ignored.
            'status' => 'manifested',
        ])->assertRedirect();

        $desire = $user->desires()->first();

        $this->assertSame('desired', $desire->status, 'A posted status was honoured.');
        $this->assertNotNull($desire->status_changed_at, 'status_changed_at was silently dropped.');
    }

    public function test_a_story_is_written_and_lands_ready(): void
    {
        $this->fakeStory("It is early and the house is quiet.\n\nThree years ago I would have checked the balance first.\n\nI rinse the cup and set it down.");

        $user = $this->user();
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect();

        $story = $user->stories()->first();

        $this->assertSame('ready', $story->state);
        $this->assertCount(3, $story->paragraphs());
        $this->assertStringContainsString('Three years ago', $story->text());
        $this->assertSame('claude-sonnet-5', $story->model);
        $this->assertSame('A quiet house', $story->title);
    }

    public function test_the_prompt_carries_the_users_own_specifics(): void
    {
        $this->fakeStory('A reading.');

        $user = $this->user();
        $user->circle()->create(['name' => 'Maya', 'relationship' => 'my daughter']);
        $desire = $user->desires()->create([
            'title' => 'A house on the water',
            'description' => 'Coffee on the deck before anyone is up',
            'people_involved' => ['Maya'],
            'status' => 'desired',
        ]);

        $this->actingAs($user)->post(route('stories.store', $desire));

        Http::assertSent(function ($request) {
            $body = $request->body();

            // The identifying details must reach the model...
            foreach (['Mark', 'Atlanta', 'Maya', 'A house on the water', 'Coffee on the deck'] as $needle) {
                if (! str_contains($body, $needle)) {
                    return false;
                }
            }

            // ...and the non-negotiable rules must travel with them.
            return str_contains($body, 'Present tense throughout')
                && str_contains($body, 'contrast beat')
                && $request->hasHeader('x-api-key', 'sk-ant-fake')
                && $request->hasHeader('anthropic-version');
        });
    }

    public function test_a_failed_story_is_reported_plainly_and_costs_a_ledger_row(): void
    {
        config(['escalate.anthropic.key' => 'sk-ant-fake']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['type' => 'overloaded_error']], 529)]);

        $user = $this->user();
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $story = $user->stories()->make();
        $story->forceFill(['desire_id' => $desire->id, 'state' => 'queued'])->save();

        // Resolve handle() through the container so its dependencies are
        // injected the way the queue would inject them.
        $job = new WriteStory($story);

        try {
            app()->call([$job, 'handle']);
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        $story->refresh();
        $this->assertSame('failed', $story->state);
        $this->assertStringNotContainsString('overloaded_error', $story->failure_reason);
        $this->assertStringContainsString('could not be written', $story->failure_reason);

        // A refused call is recorded but must not eat the user's daily quota.
        $this->assertDatabaseHas('ai_events', ['kind' => 'story', 'ok' => false, 'error_code' => 'overloaded_error']);
        $this->assertSame(config('escalate.quotas.stories_per_day'), \App\Support\Quota::remaining($user, 'story'));
    }

    public function test_narration_lands_on_the_private_disk_and_is_never_public(): void
    {
        Storage::fake('private');
        config(['escalate.elevenlabs.key' => 'el-fake']);
        Http::fake(['api.elevenlabs.io/*' => Http::response($this->fakeAudio(), 200)]);

        $user = $this->user();
        $story = $this->makeReadyStory($user);

        $this->actingAs($user)->post(route('stories.narrate', $story))->assertRedirect();

        $narration = $story->narrations()->first();

        $this->assertSame('ready', $narration->state);
        $this->assertStringStartsWith("audio/{$user->id}/", $narration->path);
        Storage::disk('private')->assertExists($narration->path);
        $this->assertNotNull($narration->duration_ms);
        $this->assertGreaterThan(0, $narration->duration_ms);

        // The disk must have no public URL at all.
        $this->assertFalse(config('filesystems.disks.private.serve'));
    }

    public function test_the_same_text_is_never_narrated_twice(): void
    {
        Storage::fake('private');
        config(['escalate.elevenlabs.key' => 'el-fake']);
        Http::fake(['api.elevenlabs.io/*' => Http::response($this->fakeAudio(), 200)]);

        $user = $this->user();

        // Two separate stories that happen to hold identical text — the case
        // the content hash exists for. The same voice is essential: a different
        // voice is different audio and must be billed for.
        $body = "It is early and the house is quiet.\n\nI rinse the cup.";
        $first  = Narration::queueFor($this->makeReadyStory($user, $body), 'still');
        app()->call([new NarrateStory($first), 'handle']);

        $second = Narration::queueFor($this->makeReadyStory($user, $body), 'still');
        app()->call([new NarrateStory($second), 'handle']);

        $this->assertSame($first->fresh()->path, $second->fresh()->path);
        $this->assertSame(1, AiEvent::where('kind', 'narration')->where('ok', true)->count(),
            'The identical text was narrated — and billed for — twice.');
        $this->assertCount(1, Storage::disk('private')->allFiles());
    }

    public function test_an_empty_response_is_a_failure_rather_than_silent_audio(): void
    {
        Storage::fake('private');
        config(['escalate.elevenlabs.key' => 'el-fake']);
        // A 200 with a tiny body: an error page wearing a success status.
        Http::fake(['api.elevenlabs.io/*' => Http::response('nope', 200)]);

        $user = $this->user();
        $story = $this->makeReadyStory($user);
        $narration = Narration::queueFor($story, 'still');

        $this->expectException(\RuntimeException::class);

        try {
            app()->call([new NarrateStory($narration), 'handle']);
        } finally {
            $this->assertCount(0, Storage::disk('private')->allFiles());
        }
    }

    public function test_the_daily_quota_stops_further_spending(): void
    {
        $this->fakeStory('A reading.');

        $user = $this->user();
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $limit = config('escalate.quotas.stories_per_day');

        for ($i = 0; $i < $limit; $i++) {
            AiEvent::create(['user_id' => $user->id, 'kind' => 'story', 'provider' => 'anthropic', 'ok' => true]);
        }

        $this->assertFalse(\App\Support\Quota::allows($user, 'story'));

        $before = $user->stories()->count();
        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect();

        $this->assertSame($before, $user->stories()->count(), 'A story was created past the quota.');
    }

    public function test_editing_a_story_clears_audio_that_read_the_old_words(): void
    {
        Storage::fake('private');

        $user = $this->user();
        $story = $this->makeReadyStory($user);
        $this->makeReadyNarration($story, 'still', 'old');

        $this->actingAs($user)->put(route('stories.update', $story), [
            'edited_body' => str_repeat('a new sentence entirely. ', 20),
        ])->assertRedirect(route('stories.show', $story));

        $this->assertSame(0, $story->narrations()->count());
        Storage::disk('private')->assertMissing('audio/'.$user->id.'/old.mp3');
    }
}
