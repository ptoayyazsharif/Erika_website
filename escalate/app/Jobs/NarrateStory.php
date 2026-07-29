<?php

namespace App\Jobs;

use App\Models\Narration;
use App\Services\Ai\ElevenLabs;
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
class NarrateStory implements ShouldQueue
{
    use Queueable;

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

        $bytes = $tts->narrate($text, $voiceId, $this->narration->user);

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
        $this->narration->markFailed(
            'The narration could not be rendered just now. The words are still here to read.',
        );

        report($e);
    }
}
