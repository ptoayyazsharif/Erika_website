<?php

namespace Tests\Feature;

use App\Models\Narration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nothing the server knows may reach the client.
 *
 * The requirement was "no user side APIs or shit like that". These tests are how
 * that requirement stops being a promise: they assert on the actual bytes sent
 * to a browser, so a future change that renders a key or a voice id into a page
 * fails here rather than in production.
 */
class NoLeakTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return $this->makeUser('leak@escalate.test');
    }

    public function test_no_page_contains_a_provider_key_or_voice_id(): void
    {
        config([
            'escalate.anthropic.key'  => 'sk-ant-test-SHOULD-NEVER-APPEAR',
            'escalate.elevenlabs.key' => 'el-test-SHOULD-NEVER-APPEAR',
        ]);

        $user = $this->user();
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);
        $story = $this->makeReadyStory($user);

        $forbidden = [
            'sk-ant-test-SHOULD-NEVER-APPEAR',
            'el-test-SHOULD-NEVER-APPEAR',
            config('escalate.voices.still.id'),
            'api.anthropic.com',
            'api.elevenlabs.io',
        ];

        foreach ([
            route('world.edit'),
            route('desires.index'),
            route('desires.create'),
            route('desires.show', $desire),
            route('stories.index'),
            route('stories.show', $story),
        ] as $url) {
            $body = $this->actingAs($user)->get($url)->assertOk()->getContent();

            foreach ($forbidden as $secret) {
                $this->assertStringNotContainsString(
                    $secret, $body, "{$url} leaked a server-side value",
                );
            }
        }
    }

    public function test_the_state_endpoint_reveals_no_provider_detail(): void
    {
        $user = $this->user();
        $story = $this->makeReadyStory($user, "First paragraph here.\n\nSecond paragraph here.");
        $story->forceFill(['model' => 'claude-sonnet-5'])->save();

        $payload = $this->actingAs($user)
            ->getJson(route('stories.state', $story))
            ->assertOk()
            ->json();

        $this->assertSame(['state', 'words', 'paragraphs', 'reason', 'audio'], array_keys($payload));
        $this->assertSame(['state', 'url', 'reason', 'seconds'], array_keys($payload['audio']));

        $encoded = json_encode($payload);
        $this->assertStringNotContainsString('claude', $encoded);
        $this->assertStringNotContainsString('eleven', $encoded);
        $this->assertStringNotContainsString('anthropic', $encoded);
    }

    public function test_audio_is_referenced_by_route_and_never_by_path(): void
    {
        Storage::fake('private');

        $user = $this->user();
        $story = $this->makeReadyStory($user);
        $this->makeReadyNarration($story, 'still', 'secret-hash');

        $body = $this->actingAs($user)->get(route('stories.show', $story))->assertOk()->getContent();

        $this->assertStringNotContainsString('storage/', $body);
        $this->assertStringNotContainsString('audio/'.$user->id, $body);
        $this->assertStringContainsString('/media/narration/', $body);
    }

    public function test_private_media_is_never_marked_publicly_cacheable(): void
    {
        Storage::fake('private');

        $user = $this->user();
        $narration = $this->makeReadyNarration($this->makeReadyStory($user));

        $cacheControl = $this->actingAs($user)
            ->get(route('media.narration', $narration))
            ->assertOk()
            ->headers->get('Cache-Control');

        // "public" here would invite shared caches to keep a recording of
        // somebody's journal. Symfony's BinaryFileResponse adds it by default,
        // so this assertion is guarding against a framework default, not a typo.
        $this->assertStringNotContainsString('public', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_user_written_content_is_encrypted_at_rest(): void
    {
        $user = $this->user();

        $user->desires()->create([
            'title' => 'THE-PLAINTEXT-TITLE',
            'description' => 'THE-PLAINTEXT-DESCRIPTION',
            'status' => 'desired',
        ]);

        // Read the raw column, bypassing Eloquent's casts entirely.
        $raw = DB::table('desires')->first();

        $this->assertStringNotContainsString('THE-PLAINTEXT-TITLE', $raw->title);
        $this->assertStringNotContainsString('THE-PLAINTEXT-DESCRIPTION', (string) $raw->description);

        // And it still round-trips through the model.
        $this->assertSame('THE-PLAINTEXT-TITLE', $user->desires()->first()->title);
    }

    public function test_registration_cannot_grant_the_admin_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@escalate.test',
            'password' => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
            'agree' => '1',
            'age' => '1',
            'role' => 'admin',
            'suspended_at' => null,
        ])->assertRedirect(route('world.edit'));

        $user = User::where('email', 'sneaky@escalate.test')->first();

        $this->assertSame('user', $user->role);
        // Consent is recorded with a timestamp, so it can be evidenced rather
        // than asserted.
        $this->assertNotNull($user->profile->consented_at);
    }

    public function test_registration_requires_consent_and_an_age_confirmation(): void
    {
        $base = [
            'name' => 'Nope',
            'email' => 'nope@escalate.test',
            'password' => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
        ];

        // Neither may be inferred from silence — this app sends what people
        // write to two AI companies.
        $this->post(route('register.store'), $base)->assertSessionHasErrors(['agree', 'age']);
        $this->post(route('register.store'), $base + ['agree' => '1'])->assertSessionHasErrors('age');
        $this->post(route('register.store'), $base + ['age' => '1'])->assertSessionHasErrors('agree');

        $this->assertNull(User::where('email', 'nope@escalate.test')->first());
    }

    public function test_the_privacy_disclosure_is_readable_without_an_account(): void
    {
        // It must be readable BEFORE signing up, or consenting to it is theatre.
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Anthropic')
            ->assertSee('ElevenLabs')
            ->assertSee('end-to-end encryption')
            // The sentence that matters most: the difference between "we do
            // not" and "we cannot" has to be said out loud.
            ->assertSee('we could read your journal if we chose to');
    }

    public function test_the_admin_area_is_invisible_to_an_ordinary_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin')->assertNotFound();
        $this->actingAs($user)->get(route('admin.login'))->assertNotFound();
        $this->actingAs($user)->post(route('admin.login.store'), ['password' => 'a-long-enough-password-1'])
            ->assertNotFound();
    }

    public function test_an_admin_still_needs_the_second_door(): void
    {
        $admin = $this->user();
        $admin->forceFill(['role' => 'admin'])->save();

        // Role alone is not enough — no admin.verified flag on the session yet.
        // Sent to the password door rather than 404'd: the admin area still
        // does not open, and only a holder of an admin account can see the
        // difference, which is all the 404 was ever protecting.
        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.login'));

        $this->actingAs($admin)->get(route('admin.login'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.login.store'), ['password' => 'a-long-enough-password-1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
