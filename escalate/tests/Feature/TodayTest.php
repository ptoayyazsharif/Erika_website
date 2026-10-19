<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Today is the screen every session lands on.
 *
 * It was the shared "not written yet" placeholder, which meant the first thing
 * anyone saw after signing in was an empty page whose only button read "Back
 * to Today" — a link to the page they were already on. Naming a desire is the
 * whole product, and nothing on the landing screen led there.
 *
 * These tests pin the two things that made it useless: it must offer a way
 * forward, and that way must never be a link to itself.
 */
class TodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_is_pointed_at_naming_its_first_desire(): void
    {
        $user = $this->makeUser('new@escalate.test', 'Erika');

        $this->actingAs($user)->get(route('today'))
            ->assertOk()
            ->assertSee('Name your first desire')
            ->assertSee(route('desires.create'));
    }

    public function test_a_desire_without_a_reading_is_surfaced_first(): void
    {
        $user = $this->makeUser('waiting@escalate.test', 'Erika');
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $this->actingAs($user)->get(route('today'))
            ->assertOk()
            ->assertSee('Ready to be written')
            ->assertSee('A quiet house')
            ->assertSee(route('desires.show', $desire));
    }

    public function test_once_everything_is_written_the_latest_reading_is_offered(): void
    {
        $user = $this->makeUser('done@escalate.test', 'Erika');
        // Attached to a desire, because a reading can only ever be created
        // from one — the route is nested under /desires/{desire}/stories.
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $this->makeReadyStory($user, null, $desire->id);

        $this->actingAs($user)->get(route('today'))
            ->assertOk()
            ->assertSee('Your most recent reading')
            ->assertSee(route('stories.show', $story))
            ->assertDontSee('Ready to be written');
    }

    /**
     * The bug itself, stated as a rule.
     *
     * A screen offering a button back to itself is the clearest possible sign
     * nobody opened it. Cheap to assert, and it would have caught the original.
     */
    public function test_no_screen_offers_a_button_back_to_the_screen_it_is_on(): void
    {
        $user = $this->makeUser('loop@escalate.test', 'Erika');

        foreach (['today', 'rewinds.index', 'journey', 'desires.index', 'gratitude.index'] as $name) {
            $url = route($name);
            $body = $this->actingAs($user)->get($url)->assertOk()->getContent();

            // The nav links to every screen by design, so only the page's own
            // action area is examined — everything after the closing </nav>.
            $content = str_contains($body, '</nav>') ? substr($body, strrpos($body, '</nav>')) : $body;

            $this->assertStringNotContainsString(
                'class="btn btn-ghost" href="'.$url.'"',
                $content,
                "{$name} offers a button linking to itself.",
            );
        }
    }
}
