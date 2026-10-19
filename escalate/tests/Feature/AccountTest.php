<?php

namespace Tests\Feature;

use App\Models\Narration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'a-long-enough-password-1';

    public function test_deleting_an_account_removes_the_audio_from_disk(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('leaving@escalate.test');
        $story = $this->makeReadyStory($user);
        $narration = $this->makeReadyNarration($story);
        $path = $narration->path;

        Storage::disk('private')->assertExists($path);

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => self::PASSWORD,
            'confirm' => 'DELETE',
        ])->assertRedirect(route('login'));

        $this->assertNull(User::find($user->id));

        // The point of the whole AccountEraser service. Database cascades do
        // not fire Eloquent events, so a plain $user->delete() would wipe every
        // row and leave a spoken recording of a deleted person's journal on
        // disk, in a directory named after their user id.
        Storage::disk('private')->assertMissing($path);
        $this->assertEmpty(Storage::disk('private')->allFiles("audio/{$user->id}"));
    }

    public function test_deletion_needs_the_password_and_the_word(): void
    {
        $user = $this->makeUser('careful@escalate.test');

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'wrong-password-entirely',
            'confirm' => 'DELETE',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => self::PASSWORD,
            'confirm' => 'delete',   // lowercase is not the word
        ])->assertSessionHasErrors('confirm');

        $this->assertNotNull(User::find($user->id));
    }

    public function test_deleting_a_story_takes_its_audio_with_it(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('tidy@escalate.test');
        $story = $this->makeReadyStory($user);
        $path = $this->makeReadyNarration($story)->path;

        $this->actingAs($user)->delete(route('stories.destroy', $story))->assertRedirect();

        // destroy() was the one path that relied on the database cascade, and
        // therefore the one that orphaned the file — while being the action
        // people use when they actually want something gone.
        Storage::disk('private')->assertMissing($path);
        $this->assertSame(0, Narration::count());
    }

    public function test_deleting_a_desire_really_does_take_its_readings(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('honest@escalate.test');
        $desire = $user->desires()->create(['title' => 'A quiet house', 'status' => 'desired']);

        $story = $user->stories()->make();
        $story->forceFill([
            'desire_id' => $desire->id, 'state' => 'ready', 'body' => str_repeat('word ', 200),
        ])->save();
        $path = $this->makeReadyNarration($story)->path;

        $this->actingAs($user)
            ->delete(route('desires.destroy', $desire))
            ->assertRedirect(route('desires.index'));

        // stories.desire_id is nullOnDelete, so the readings used to survive
        // while the message claimed they were gone. They contain the same names
        // and details as the desire did.
        $this->assertSame(0, $user->stories()->count());
        Storage::disk('private')->assertMissing($path);
    }

    public function test_an_export_contains_the_journal_in_readable_form(): void
    {
        $user = $this->makeUser('export@escalate.test');
        $user->world()->update(['preferred_name' => 'Mark', 'city' => 'Atlanta']);
        $user->circle()->create(['name' => 'Maya', 'relationship' => 'my daughter']);
        $user->desires()->create(['title' => 'A house on the water', 'status' => 'desired']);
        $user->gratitudeEntries()->create(['body' => 'The dock at six', 'for_date' => today()]);

        $response = $this->actingAs($user)
            ->post(route('account.export'), ['password' => self::PASSWORD])
            ->assertOk();

        $json = json_decode($response->streamedContent(), true);

        // Decrypted on the way out — an export nobody can read is not an export.
        $this->assertSame('Mark', $json['my_world']['preferred_name']);
        $this->assertSame('Maya', $json['circle'][0]['name']);
        $this->assertSame('A house on the water', $json['desires'][0]['title']);
        $this->assertSame('The dock at six', $json['gratitude'][0]['body']);
    }

    public function test_an_export_needs_the_password(): void
    {
        $user = $this->makeUser('export@escalate.test');

        $this->actingAs($user)
            ->post(route('account.export'), ['password' => 'not-the-right-one'])
            ->assertSessionHasErrors('password');
    }

    public function test_a_stranger_cannot_delete_or_export_another_account(): void
    {
        $owner = $this->makeUser('owner@escalate.test');
        $stranger = $this->makeUser('stranger@escalate.test');

        // There is no user id in either route — they act on the session's own
        // account and cannot be pointed at anyone else. Prove the stranger's
        // request only ever touches the stranger.
        $this->actingAs($stranger)->delete(route('account.destroy'), [
            'password' => self::PASSWORD,
            'confirm' => 'DELETE',
        ])->assertRedirect(route('login'));

        $this->assertNotNull(User::find($owner->id));
        $this->assertNull(User::find($stranger->id));
    }
}
