<?php

namespace Tests\Feature;

use App\Models\Narration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regressions for findings an adversarial review turned up.
 *
 * Each of these was a real defect that no existing test would have caught, and
 * each would come back silently.
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_forged_forwarded_header_cannot_change_the_client_ip(): void
    {
        // trustProxies used to be at:'*', which made $request->ip() whatever
        // the caller put in X-Forwarded-For — collapsing the login lockout and
        // every rate limit in the app.
        $this->get('/login', ['X-Forwarded-For' => '1.2.3.4']);

        $this->assertNotSame('1.2.3.4', request()->ip());
    }

    public function test_the_login_lockout_survives_a_rotating_source_address(): void
    {
        $user = $this->makeUser('locked@escalate.test');

        // Six failures, each from a different address. The email+IP bucket is
        // fresh every time; the email-only bucket is not.
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login.store'), [
                'email' => 'locked@escalate.test',
                'password' => 'wrong-'.$i,
            ], ['X-Forwarded-For' => "10.0.0.{$i}"]);
        }

        // The correct password must still be refused while the lockout holds.
        $this->post(route('login.store'), [
            'email' => 'locked@escalate.test',
            'password' => 'a-long-enough-password-1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_gratitude_page_never_loads_an_unbounded_number_of_entries(): void
    {
        $user = $this->makeUser('many@escalate.test');
        $window = \App\Http\Controllers\GratitudeController::SEARCH_WINDOW;

        foreach (range(1, $window + 5) as $i) {
            $user->gratitudeEntries()->create([
                'body' => "Entry number {$i}",
                'for_date' => today()->subDays($i % 300),
            ]);
        }

        $response = $this->actingAs($user)->get(route('gratitude.index'))->assertOk();

        // Every row is decrypted and rendered, so an unbounded query is a
        // memory fatal on shared hosting, not just a slow page.
        $this->assertLessThanOrEqual($window, $response->viewData('grouped')->flatten()->count());
        $this->assertTrue($response->viewData('truncated'));
        $response->assertSee('most recent');
    }

    public function test_array_query_parameters_do_not_crash_the_gratitude_page(): void
    {
        $user = $this->makeUser('arr@escalate.test');

        // Casting ?q[]=x to a string raised a PHP warning, which Laravel turns
        // into an ErrorException — a 500 any signed-in user could spam.
        $this->actingAs($user)->get('/gratitude?q[]=x')->assertOk();
        $this->actingAs($user)->get('/gratitude?tag[]=x')->assertOk();
    }

    public function test_like_wildcards_in_a_tag_filter_are_not_treated_as_wildcards(): void
    {
        $user = $this->makeUser('wild@escalate.test');

        $user->gratitudeEntries()->create(['body' => 'VISIBLE', 'tags' => ['work'], 'for_date' => today()]);
        $user->gratitudeEntries()->create(['body' => 'ALSOVISIBLE', 'tags' => ['home'], 'for_date' => today()]);

        // An unescaped % would match every tagged entry, quietly turning a
        // filtered page back into the unbounded one.
        $this->actingAs($user)->get(route('gratitude.index', ['tag' => '%']))
            ->assertOk()
            ->assertDontSee('VISIBLE')
            ->assertDontSee('ALSOVISIBLE');
    }

    public function test_deleting_one_narration_does_not_destroy_audio_another_still_uses(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('shared@escalate.test');
        $storyA = $this->makeReadyStory($user);
        $storyB = $this->makeReadyStory($user);

        $a = $this->makeReadyNarration($storyA, 'still', 'shared-hash');

        // The dedupe in NarrateStory points a second row at the same file when
        // the content hash matches — that is what stops a user paying twice.
        $b = Narration::query()->make();
        $b->forceFill([
            'story_id' => $storyB->id, 'user_id' => $user->id, 'voice' => 'still',
            'state' => 'ready', 'path' => $a->path, 'content_hash' => 'shared-hash',
        ])->save();

        $b->delete();

        // A's audio must survive B's deletion.
        Storage::disk('private')->assertExists($a->path);
        $this->assertTrue($a->fresh()->isReady());

        // And once the last row goes, so does the file.
        $a->fresh()->delete();
        Storage::disk('private')->assertMissing($a->path);
    }

    public function test_json_responses_are_not_storable(): void
    {
        $user = $this->makeUser('json@escalate.test');
        $story = $this->makeReadyStory($user);

        // /stories/{id}/state returns the full text of a reading and is polled
        // every couple of seconds; under Symfony's default those responses were
        // written to the browser's disk cache in plaintext.
        $cacheControl = $this->actingAs($user)
            ->getJson(route('stories.state', $story))
            ->assertOk()
            ->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_a_suspended_session_is_told_to_clear_itself(): void
    {
        $user = $this->makeUser('susp@escalate.test');
        $user->forceFill(['suspended_at' => now()])->save();

        // RejectSuspended intercepts POST /logout too, so the ordinary
        // sign-out response never runs for this account.
        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_asking_for_the_profile_twice_does_not_try_a_second_insert(): void
    {
        $user = $this->makeUser('twice@escalate.test');
        $user->profile()->delete();
        $user->unsetRelation('profile');

        // create() does not populate the cached relation, so the second call
        // used to attempt another INSERT and hit the unique index.
        $first = $user->world();
        $second = $user->world();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $user->profile()->count());
    }
}
