<?php

namespace Tests;

use App\Models\Narration;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * A user with a profile, ready to act as.
     */
    protected function makeUser(string $email = 'user@escalate.test', string $name = 'Test'): User
    {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => 'a-long-enough-password-1',
        ]);

        $user->profile()->create(['preferred_name' => $name]);

        return $user;
    }

    /**
     * A story already in the ready state.
     *
     * Uses forceFill for the same reason the application does: `state` and
     * `desire_id` are not mass-assignable, so a test that reached for create()
     * would silently get a queued story with no desire — which is exactly the
     * bug this helper exists to stop being re-introduced.
     */
    protected function makeReadyStory(User $user, ?string $body = null, ?int $desireId = null): Story
    {
        $story = $user->stories()->make();

        $story->forceFill([
            'desire_id'  => $desireId,
            'state'      => 'ready',
            'body'       => $body ?? "It is early and the house is quiet.\n\nThree years ago I would have checked first.\n\nI rinse the cup.",
        ])->save();

        return $story->refresh();
    }

    /**
     * A narration with a real file behind it on the faked private disk.
     * Call Storage::fake('private') first.
     */
    protected function makeReadyNarration(Story $story, string $voice = 'still', string $name = 'x'): Narration
    {
        $path = "audio/{$story->user_id}/{$name}.mp3";
        Storage::disk('private')->put($path, str_repeat('a', 4096));

        $narration = Narration::queueFor($story, $voice);
        $narration->markReady(['path' => $path, 'bytes' => 4096, 'duration_ms' => 120000]);

        return $narration;
    }
}
