<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One announcement, to one person.
 *
 * One job per recipient rather than one job for the list, for the reason
 * Mailer::toAdmins gives: a single bad address must not take the rest down with
 * it. A hundred tiny jobs on a database queue with a worker already running is
 * nothing; a single job that dies halfway through a hundred sends leaves nobody
 * able to say who got it.
 *
 * The opt-out is re-checked here, not only when the jobs were dispatched.
 * Between dispatch and delivery somebody can click unsubscribe — on a large
 * list that window is minutes, and honouring a click that arrived during it is
 * the difference between an unsubscribe that works and one that appears to.
 */
class SendAnnouncementEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public array $backoff = [30];

    public function __construct(
        public Announcement $announcement,
        public User $recipient,
    ) {}

    public function handle(): void
    {
        if (! $this->recipient->wantsAnnouncementEmails()) {
            return;
        }

        Mailer::send($this->recipient->email, new AnnouncementMail($this->announcement, $this->recipient));
    }
}
