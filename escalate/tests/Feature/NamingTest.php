<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use App\Services\Ai\Anthropic;
use App\Services\AffirmationWriter;
use App\Services\StoryWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nobody appears in a reading who the reader did not put there.
 *
 * A desire that named nobody — "whole family together enjoying in ranch, all
 * brothers and their kids live at same place" — came back with a brother called
 * Zarak, who exists nowhere in the account. It was not the model wandering off.
 * Rule 4 of the story prompt said the names of other people in the reader's life
 * "must appear at least once" and, in the next breath, "do not invent names for
 * people they did not name". Told a name must appear and given none, a model has
 * one move left.
 *
 * These tests assert the prompt rather than the prose, because the prompt is
 * what was wrong and it is the only part of this we control exactly.
 */
class NamingTest extends TestCase
{
    use RefreshDatabase;

    /** Captures what would have been sent, and returns whatever we like. */
    private function capture(string $returns = "A piece.\n\nNAMES: none"): object
    {
        $spy = new class($returns)
        {
            public string $system = '';

            public string $user = '';

            public function __construct(public string $returns) {}
        };

        $this->mock(Anthropic::class, function ($mock) use ($spy) {
            $mock->shouldReceive('configured')->andReturn(true);
            $mock->shouldReceive('write')->andReturnUsing(
                function ($system, $user) use ($spy) {
                    $spy->system = $system;
                    $spy->user = $user;

                    return $spy->returns;
                },
            );
        });

        return $spy;
    }

    private function reader(string $email = 'reader@escalate.test'): User
    {
        return $this->makeUser($email);
    }

    private function storyFor(User $user, array $desireAttributes = []): Story
    {
        $desire = $user->desires()->create(array_merge([
            'title' => 'A huge family ranch',
            'description' => 'Whole family together enjoying in ranch, all brothers and their kids live at same place',
        ], $desireAttributes));

        $story = $user->stories()->make();
        $story->forceFill(['desire_id' => $desire->id, 'state' => 'writing'])->save();

        return $story;
    }

    /* ── the reading that started this ───────────────────────────────────── */

    /**
     * The exact shape of the bug: a desire about brothers, nobody attached.
     *
     * The old prompt told the model a name had to appear. The new one tells it
     * what to write instead, which is a far more reliable instruction than a
     * prohibition — and is what the model was already doing well elsewhere in
     * the same piece ("one of my brothers' kids is arguing with a dog").
     */
    public function test_a_desire_naming_nobody_forbids_naming_anybody(): void
    {
        $spy = $this->capture();
        $user = $this->reader();

        app(StoryWriter::class)->write($this->storyFor($user));

        $this->assertStringContainsString('NOBODY IS NAMED IN THIS PIECE', $spy->system);
        $this->assertStringContainsString('my brother', $spy->system);

        // The clause that caused it must be gone, not merely softened.
        $this->assertStringNotContainsString(
            'so must the names of other people in their life',
            $spy->system,
            'The mandate that invented a name is still in the prompt.',
        );
    }

    /** A circle member nobody attached to this desire is not even sent. */
    public function test_the_rest_of_the_circle_never_reaches_the_prompt(): void
    {
        $spy = $this->capture();
        $user = $this->reader();

        $user->circle()->create(['name' => 'Maya', 'relationship' => 'my daughter', 'position' => 0]);
        $user->circle()->create(['name' => 'Idris', 'relationship' => 'my brother', 'position' => 1]);

        app(StoryWriter::class)->write($this->storyFor($user));

        $this->assertStringNotContainsString('Maya', $spy->user);
        $this->assertStringNotContainsString('Idris', $spy->user);
    }

    /** The ones they did attach are sent, and only those. */
    public function test_only_the_people_attached_to_this_desire_are_sent(): void
    {
        $spy = $this->capture();
        $user = $this->reader();

        $user->circle()->create(['name' => 'Maya', 'relationship' => 'my daughter', 'position' => 0]);
        $user->circle()->create(['name' => 'Idris', 'relationship' => 'my brother', 'position' => 1]);

        app(StoryWriter::class)->write(
            $this->storyFor($user, ['people_involved' => ['Idris']]),
        );

        $this->assertStringContainsString('Idris', $spy->user);
        $this->assertStringContainsString('my brother', $spy->user);
        $this->assertStringNotContainsString('Maya', $spy->user);

        // And the rule names them rather than demanding they appear.
        $this->assertStringContainsString('Idris', $spy->system);
        $this->assertStringContainsString('none of them has to appear', $spy->system);
    }

    /* ── the net ─────────────────────────────────────────────────────────── */

    /** A piece that declares a stranger is refused, not stored. */
    public function test_a_reading_that_names_a_stranger_is_not_stored(): void
    {
        $this->capture("Zarak calls out from the second house.\n\nNAMES: Zarak");

        $user = $this->reader();
        $story = $this->storyFor($user);

        $this->expectException(\RuntimeException::class);

        try {
            app(StoryWriter::class)->write($story);
        } finally {
            $this->assertNull($story->fresh()->body, 'A stranger was written into the journal.');
            $this->assertNotSame('ready', $story->fresh()->state);
        }
    }

    /** Somebody the reader named is fine, in any casing. */
    public function test_a_reading_naming_someone_they_attached_is_kept(): void
    {
        $this->capture("Idris calls out from the second house.\n\nNAMES: idris");

        $user = $this->reader();
        $user->circle()->create(['name' => 'Idris', 'relationship' => 'my brother', 'position' => 0]);

        $story = $this->storyFor($user, ['people_involved' => ['Idris']]);

        app(StoryWriter::class)->write($story);

        $this->assertSame('ready', $story->fresh()->state);
        $this->assertStringContainsString('Idris', $story->fresh()->body);
    }

    /** The declaration is machinery and must never reach the reader. */
    public function test_the_names_line_is_stripped_from_what_anybody_reads(): void
    {
        $this->capture("The porch runs the length of the house.\n\nNAMES: none");

        $story = $this->storyFor($this->reader());

        app(StoryWriter::class)->write($story);

        $body = $story->fresh()->body;

        $this->assertStringNotContainsString('NAMES', $body);
        $this->assertStringContainsString('The porch runs the length of the house.', $body);
    }

    /* ── the same fault in the cards ─────────────────────────────────────── */

    public function test_cards_name_nobody_when_nobody_was_attached(): void
    {
        $spy = $this->capture("FRONT: I keep the mornings.\nBACK: You already do.");

        $user = $this->reader('cards@escalate.test');
        $user->circle()->create(['name' => 'Maya', 'relationship' => 'my daughter', 'position' => 0]);
        $user->desires()->create(['title' => 'A huge family ranch']);

        $set = new \App\Models\AffirmationSet;
        $set->forceFill(['user_id' => $user->id, 'for_date' => today(), 'state' => 'writing'])->save();

        app(AffirmationWriter::class)->write($set);

        $this->assertStringContainsString('Name nobody', $spy->system);
        $this->assertStringNotContainsString('Maya', $spy->user);
    }
}
