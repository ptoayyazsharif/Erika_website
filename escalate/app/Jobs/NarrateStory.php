<?php

namespace App\Jobs;

use App\Models\Narration;
use App\Services\Ai\ElevenLabs;
use App\Services\Ai\NarrationFailed;
use App\Support\Quota;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Renders a narration to the private disk.
 *
 * Narration is the expensive half of this app — roughly sixteen times the cost
 * of the words — so the content hash matters. If the same user has already
 * narrated identical text in the same voice, the existing file is reused and
 * nothing is billed. Editing a story changes the hash and therefore costs
 * again, which is correct: it is different audio.
 */
class NarrateStory implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /*
    | Narration is the expensive half of this app, so it gets the same
    | uniqueness lock as WriteStory — see the note there.
    */
    public function uniqueId(): string
    {
        return 'narrate-'.$this->narration->id;
    }

    public int $uniqueFor = 900;

    public int $tries = 2;

    public int $timeout = 240;

    public array $backoff = [15];

    public function __construct(public Narration $narration) {}

    public function handle(ElevenLabs $tts): void
    {
        if ($this->narration->isReady()) {
            return;
        }

        // Same reasoning as WriteStory: an absent key is not a transient fault.
        if (! $tts->configured()) {
            $this->fail(new \RuntimeException('No narration provider is configured.'));

            return;
        }

        // Re-check at the point of spending — see the note in WriteStory.
        if (Quota::used($this->narration->user, 'narration') > Quota::limit('narration')) {
            $this->narration->markFailed(Quota::message('narration'));

            return;
        }

        $story = $this->narration->story;
        $text = $story->text();

        if (trim($text) === '') {
            throw new \RuntimeException('There is no text to narrate.');
        }

        $hash = hash('sha256', $text.'|'.$this->narration->voice.'|'.config('escalate.voice.model'));

        // Already rendered for this user, byte for byte. Point at it and stop.
        $existing = Narration::where('user_id', $this->narration->user_id)
            ->where('content_hash', $hash)
            ->where('state', 'ready')
            ->whereNotNull('path')
            ->where('id', '!=', $this->narration->id)
            ->first();

        if ($existing && Storage::disk('private')->exists($existing->path)) {
            $this->narration->markReady([
                'content_hash' => $hash,
                'path'         => $existing->path,
                'bytes'        => $existing->bytes,
                'duration_ms'  => $existing->duration_ms,
                'characters'   => $existing->characters,
                'model'        => $existing->model,
            ]);

            return;
        }

        $this->narration->markRendering($hash);

        $voiceId = config("escalate.voices.{$this->narration->voice}.id")
            ?? config('escalate.voices.still.id');

        try {
            $bytes = $tts->narrate($text, $voiceId, $this->narration->user);
        } catch (NarrationFailed $e) {
            // An empty account or a bad key will fail identically on every
            // retry, and each attempt leaves the user watching "being
            // recorded…" for nothing. Stop now and say what is actually wrong.
            if (! $e->worthRetrying()) {
                $this->narration->markFailed($e->getMessage());
                $this->fail($e);

                return;
            }

            throw $e;
        }

        // Path includes the user id so a directory listing groups by owner, and
        // the filename is the hash so it reveals nothing about the content.
        $path = "audio/{$this->narration->user_id}/{$hash}.mp3";
        Storage::disk('private')->put($path, $bytes);

        $this->narration->markReady([
            'path'        => $path,
            'bytes'       => strlen($bytes),
            'duration_ms' => $tts->durationMs($bytes),
            'characters'  => mb_strlen($text),
            'model'       => config('escalate.voice.model'),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        // A NarrationFailed already carries a message written for the user, and
        // it is more specific than anything this method could say.
        $this->narration->markFailed(
            $e instanceof NarrationFailed
                ? $e->getMessage()
                : 'The narration could not be recorded just now. The words are still here to read.',
        );

        report($e);
    }
}
