<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\Ai\Anthropic;
use App\Services\StoryWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Writes a story in the background.
 *
 * Queued rather than inline because generation takes 15–25 seconds, which is
 * longer than shared hosting will hold a request open. The browser polls
 * /stories/{story}/state instead, so a dropped connection never loses a
 * reading that was already paid for.
 */
class WriteStory implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    /** Fail rather than retry forever — each attempt costs money. */
    public array $backoff = [10];

    public function __construct(public Story $story) {}

    public function handle(StoryWriter $writer, Anthropic $anthropic): void
    {
        // Another attempt already finished; do not pay twice.
        if ($this->story->state === 'ready') {
            return;
        }

        // No key means no amount of retrying will help. Fail immediately rather
        // than leaving the reveal screen spinning through a backoff for an
        // outcome that cannot change.
        if (! $anthropic->configured()) {
            $this->fail(new RuntimeException('No writing provider is configured.'));

            return;
        }

        $this->story->markWriting();

        $writer->write($this->story);
    }

    public function failed(?Throwable $e): void
    {
        // The user sees this, so it must be plain and free of stack detail.
        $this->story->markFailed(
            'The reading could not be written just now. Nothing was charged — try again in a moment.',
        );

        report($e);
    }
}
