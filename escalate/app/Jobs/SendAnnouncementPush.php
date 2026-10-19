<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\PushSubscription;
use App\Support\Push;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One announcement, to every device at once.
 *
 * ── Why this is one job and SendAnnouncementEmail is one job per person ─────
 *
 * They look like the same problem and are not. Mail has no batching primitive:
 * each send is its own SMTP conversation, so a single job looping a hundred
 * addresses means one bad address can strand the ninety-nine after it, and the
 * split is what keeps them independent.
 *
 * WebPush is the opposite shape. queueNotification() + flush() does the HTTP in
 * parallel and reports per endpoint, which is the library's designed use — and
 * it is also what prunes dead subscriptions, since App\Support\Push reads those
 * reports to decide which rows to delete. Splitting it into a job per device
 * would mean a TLS handshake each, for nothing, and would throw away the
 * pruning that keeps the table from filling with uninstalled phones.
 *
 * Worth saying where the limit is: a list in the thousands would want chunking,
 * because flush() holds every request in memory. At beta size it is one job.
 */
class SendAnnouncementPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public Announcement $announcement) {}

    /**
     * $tries is 1 deliberately. A retry cannot tell which devices already
     * buzzed — Push::send reports per endpoint but the job has no memory
     * between attempts — so a retry after a partial success notifies some
     * people twice. A notification nobody received is a smaller failure than
     * one delivered twice.
     */
    public function handle(): void
    {
        if (! Push::configured()) {
            return;
        }

        $devices = PushSubscription::query()->reachable()->get();

        if ($devices->isEmpty()) {
            return;
        }

        $result = Push::send(
            $devices,
            $this->announcement->title,
            $this->announcement->notificationBody(),
            route('today'),
            'escalate-announcement-'.$this->announcement->id,
        );

        logger()->info('Announcement pushed', [
            'announcement' => $this->announcement->id,
        ] + $result);
    }
}
