<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Half-filled forms must save.
 *
 * Every repeating field in this app renders a fixed number of boxes — six
 * values, six feelings, several tags — and nobody fills all of them. Laravel's
 * ConvertEmptyStringsToNull turns each empty box into null, and a bare
 * `string` rule rejects null, so leaving one blank failed the whole submission
 * with "The values.0 field must be a string": an error naming a field the
 * person cannot see and never typed in.
 *
 * Found by driving one account through the app in order rather than testing
 * screens one at a time — every screen passed on its own, because a test that
 * fills every box never leaves one empty.
 */
class BlankFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_world_saves_with_only_some_values_filled(): void
    {
        $user = $this->makeUser('blanks-world@escalate.test');

        $this->actingAs($user)->put(route('world.update'), [
            'voice'          => 'still',
            'faith_language' => 'none',
            'default_length' => 'short',
            'story_style'    => 'cinematic',
            'perspective'    => 'first',
            'tone'           => 'grounded',
            'theme'          => 'midnight',
            // Exactly what the browser sends: two filled, four empty.
            'values'         => ['Freedom', 'Family', null, null, null, null],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Freedom', 'Family'], $user->fresh()->world()->values);
    }

    public function test_a_desire_saves_with_blank_feelings_and_people(): void
    {
        $user = $this->makeUser('blanks-desire@escalate.test');

        $this->actingAs($user)->post(route('desires.store'), [
            'title'            => 'A quiet house',
            'category'         => 'home',
            'timeframe'        => 'this_year',
            'story_length'     => 'short',
            'perspective'      => 'first',
            'tone'             => 'grounded',
            'desired_feelings' => ['Calm', null, null],
            'people_involved'  => [null, null],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Calm'], $user->desires()->first()->desired_feelings);
    }

    public function test_gratitude_saves_with_blank_tags(): void
    {
        $user = $this->makeUser('blanks-gratitude@escalate.test');

        $this->actingAs($user)->post(route('gratitude.store'), [
            'body' => 'The light in the kitchen this morning.',
            'tags' => ['morning', null, null, null],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, $user->gratitudeEntries()->count());
    }
}
