<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The front door.
 *
 * `/` used to redirect straight to /login, which meant the funnel had no top:
 * every link in Erika's launch calendar would have landed a stranger on a
 * password form with no idea what the product was.
 */
class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stranger_gets_the_landing_page_rather_than_a_login_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Imagine it forward')
            ->assertSee('Request private access');
    }

    /** It says what the app is, not only that it exists. */
    public function test_it_explains_the_product_and_the_way_in(): void
    {
        $page = $this->get('/')->assertOk();

        $page->assertSee('invite-only', false);
        $page->assertSee(route('apply'), false);
        $page->assertSee(route('login'), false);
        $page->assertSee(route('privacy'), false);
    }

    /** Somebody who already has an account came for the app, not the pitch. */
    public function test_a_signed_in_person_still_goes_straight_to_today(): void
    {
        $this->actingAs($this->makeUser('member@escalate.test'))
            ->get('/')
            ->assertRedirect(route('today'));
    }

    /** The landing page must not be indexed while the beta is private. */
    public function test_it_is_not_offered_to_search_engines(): void
    {
        $this->get('/')->assertSee('noindex', false);
    }
}
