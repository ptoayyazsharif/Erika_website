<?php

namespace App\Jobs;

use App\Models\Rewind;
use App\Services\Ai\Anthropic;
use App\Services\RewindWriter;
use App\Support\Quota;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/** Writes a Rewind's retrospective in the background, on the same terms as WriteStory. */
class WriteRewind implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function uniqueId(): string
    {
        return 'write-rewind-'.$this->rewind->id;
    }

    public int $uniqueFor = 600;

    public int $tries = 2;

    public int $timeout = 180;

    /** Fail rather than retry forever — each attempt costs money. */
    public array $backoff = [10];

    public function __construct(public Rewind $rewind) {}

    public function handle(RewindWriter $writer, Anthropic $anthropic): void
    {
        // Another attempt already finished; do not pay twice.
        if ($this->rewind->state === 'ready') {
            return;
        }

        if (! $anthropic->configured()) {
            $this->fail(new RuntimeException('No writing provider is configured.'));

            return;
        }

        // Re-checked at the point of spending, not just when it was asked for:
        // on a backed-up queue many requests pass the controller's check before
        // any of them reaches here.
        if (Quota::used($this->rewind->user, 'rewind') >= Quota::limit('rewind')) {
            $this->markFailed(Quota::message('rewind'));

            return;
        }

        $this->rewind->forceFill(['state' => 'writing'])->save();

        $writer->write($this->rewind);
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed(
            'The Rewind could not be written just now. Your answers are saved — try again in a moment.',
        );

        report($e);
    }

    /**
     * The answers are never touched.
     *
     * They are the part the person actually wrote, and losing them to a
     * provider outage would be unforgivable — only the generated narrative and
     * the state are reset, so "try again" costs nothing but the request.
     */
    private function markFailed(string $reason): void
    {
        $this->rewind->forceFill([
            'state' => 'failed',
            'failure_reason' => $reason,
        ])->save();
    }
}
