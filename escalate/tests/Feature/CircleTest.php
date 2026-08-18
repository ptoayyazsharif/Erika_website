<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Circle grows.
 *
 * It used to be five fixed slots with one fact each, and nothing said so — you
 * found out by trying to add a sixth person. That is the wrong shape for what
 * this app is: someone met this month becomes a friend, a partner, or someone
 * you no longer speak to, and a reading is only as specific as what the app
 * knows about the people in it.
 */
class CircleTest extends TestCase
{
    use RefreshDatabase;

    /** The full My World payload, so the form's other required fields validate. */
    private function world(array $circle): array
    {
        return [
            'voice'          => 'still',
            'faith_language' => 'none',
            'default_length' => 'short',
            'story_style'    => 'cinematic',
            'perspective'    => 'first',
            'tone'           => 'grounded',
            'theme'          => 'midnight',
            'circle'         => $circle,
        ];
    }

    public function test_more_than_the_old_five_people_can_be_saved(): void
    {
        $user = $this->makeUser('circle@escalate.test');

        $circle = [];
        for ($i = 0; $i < 20; $i++) {
            $circle[] = ['name' => "Person {$i}", 'relationship' => 'friend', 'notes' => ["detail {$i}"]];
        }

        $this->actingAs($user)->put(route('world.update'), $this->world($circle))->assertRedirect();

        $this->assertCount(20, $user->fresh()->circle);
        $this->assertSame('Person 19', $user->fresh()->circle->last()->name);
    }

    public function test_one_person_can_hold_many_details(): void
    {
        $user = $this->makeUser('details@escalate.test');

        $notes = ['Drinks tea, never coffee', 'Calls on Sundays', 'Hates being photographed'];

        $this->actingAs($user)->put(route('world.update'), $this->world([
            ['name' => 'Marta', 'relationship' => 'sister', 'notes' => $notes],
        ]))->assertRedirect();

        $person = $user->fresh()->circle->first();

        $this->assertSame($notes, $person->details());
        // Every one of them encrypted at rest — these are the lines most likely
        // to say something the person would never want read.
        $this->assertStringNotContainsString('Sundays', $person->getRawOriginal('notes'));
    }

    public function test_a_relationship_can_change_without_losing_the_person(): void
    {
        $user = $this->makeUser('changes@escalate.test');

        $this->actingAs($user)->put(route('world.update'), $this->world([
            ['name' => 'Sam', 'relationship' => 'someone I just met', 'notes' => ['Met at the market']],
        ]))->assertRedirect();

        // Later, the same person, a different relationship and one more fact.
        $this->actingAs($user)->put(route('world.update'), $this->world([
            ['name' => 'Sam', 'relationship' => 'partner', 'notes' => ['Met at the market', 'Moved in in March']],
        ]))->assertRedirect();

        $person = $user->fresh()->circle->first();

        $this->assertSame('Sam', $person->name);
        $this->assertSame('partner', $person->relationship);
        $this->assertSame(['Met at the market', 'Moved in in March'], $person->details());
        $this->assertCount(1, $user->fresh()->circle, 'The person was duplicated rather than updated.');
    }

    public function test_blank_details_are_dropped_rather_than_stored(): void
    {
        $user = $this->makeUser('blanks@escalate.test');

        $this->actingAs($user)->put(route('world.update'), $this->world([
            ['name' => 'Ana', 'relationship' => '', 'notes' => ['Real detail', '', '   ']],
            ['name' => '',    'relationship' => 'nobody', 'notes' => ['orphan']],
        ]))->assertRedirect();

        $circle = $user->fresh()->circle;

        // The nameless row is not a person, so it is not stored at all.
        $this->assertCount(1, $circle);
        $this->assertSame(['Real detail'], $circle->first()->details());
    }

    public function test_the_readings_prompt_sees_every_detail(): void
    {
        $user = $this->makeUser('prompt@escalate.test');

        $this->actingAs($user)->put(route('world.update'), $this->world([
            ['name' => 'Marta', 'relationship' => 'sister', 'notes' => ['Drinks tea', 'Calls on Sundays']],
        ]))->assertRedirect();

        // The prompt builder is the whole reason the circle is stored at all;
        // details that never reach it are details the user typed for nothing.
        $prompt = (new \ReflectionMethod(\App\Services\StoryWriter::class, 'user'))
            ->invoke(app(\App\Services\StoryWriter::class), $user->fresh(), null);

        $this->assertStringContainsString('Marta', $prompt);
        $this->assertStringContainsString('Drinks tea', $prompt);
        $this->assertStringContainsString('Calls on Sundays', $prompt);
    }
}
