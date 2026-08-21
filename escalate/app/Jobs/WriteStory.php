<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\Ai\Anthropic;
use App\Services\StoryWriter;
use App\Support\Ceiling;
use App\Support\Quota;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
class WriteStory implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /*
    | Second guard against paying twice. retry_after is now longer than this
    | job's timeout, which is the real fix, but a uniqueness lock costs nothing
    | and covers the cases config cannot: a double-clicked button, a retried
    | request, two workers started by overlapping cron runs.
    */
    public function uniqueId(): string
    {
        return 'write-story-'.$this->story->id;
    }

    public int $uniqueFor = 600;

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

        /*
        | Re-check at the point of spending, not just at the point of asking.
        | The check in the controller happened before this job was queued, and
        | on a backed-up queue many requests can pass it before any of them
        | reaches here.
        |
        | inFlight() counts this job's own row, so the comparison is >= : at the
        | limit there is no room for this one.
        */
        if (Quota::used($this->story->user, 'story') >= Quota::limit('story')) {
            $this->story->markFailed(Quota::message('story'));

            return;
        }

        // The app-wide ceiling, re-checked here for the same reason as the
        // quota above: many requests can pass the controller's check before
        // any of them reaches a worker.
        if (! Ceiling::allows('story')) {
            $this->story->markFailed(Ceiling::message());

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
