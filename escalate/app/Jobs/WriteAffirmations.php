<?php

namespace App\Jobs;

use App\Models\AffirmationSet;
use App\Services\AffirmationWriter;
use App\Services\Ai\Anthropic;
use App\Support\Ceiling;
use App\Support\Quota;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Draws a day's affirmation cards in the background.
 *
 * The same shape as WriteStory, deliberately, including the guards it had to
 * be given: a uniqueness lock against double-clicks and retried requests, and
 * a re-check of both the per-person quota and the whole-application ceiling at
 * the point of spending rather than only at the point of asking. On a queue
 * running from cron the controller's check can be minutes stale, and every
 * request in that window sees a fresh allowance.
 */
class WriteAffirmations implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function uniqueId(): string
    {
        return 'write-affirmations-'.$this->set->id;
    }

    public int $uniqueFor = 600;

    public int $tries = 2;

    public int $timeout = 180;

    /** Fail rather than retry forever — each attempt costs money. */
    public array $backoff = [10];

    public function __construct(public AffirmationSet $set) {}

    public function handle(AffirmationWriter $writer, Anthropic $anthropic): void
    {
        if ($this->set->isReady()) {
            return;
        }

        if (! $anthropic->configured()) {
            $this->fail(new RuntimeException('No writing provider is configured.'));

            return;
        }

        // inFlight() counts this job's own row, so the comparison is >= : at
        // the limit there is no room for this one.
        if (Quota::used($this->set->user, 'affirmation') >= Quota::limit($this->set->user, 'affirmation')) {
            $this->markFailed(Quota::message($this->set->user, 'affirmation'));

            return;
        }

        if (! Ceiling::allows('affirmation')) {
            $this->markFailed(Ceiling::message());

            return;
        }

        $this->set->forceFill(['state' => 'writing'])->save();

        $writer->write($this->set);
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed(
            'The cards could not be drawn just now. Nothing was charged — try again in a moment.',
        );

        report($e);
    }

    private function markFailed(string $reason): void
    {
        $this->set->forceFill(['state' => 'failed', 'failure_reason' => $reason])->save();
    }
}
