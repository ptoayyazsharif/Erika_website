<?php

namespace App\Console\Commands;

use App\Support\DueReminders;
use App\Support\Push;
use Illuminate\Console\Command;

/**
 * The daily nudge, run hourly.
 *
 * Hourly rather than daily because each device carries its own timezone: this
 * wakes every hour and sends only to the devices for which it is *now* the
 * chosen hour locally. A once-a-day job would fire at nine in one place and
 * three in the morning in another, and a 3am notification is how somebody turns
 * notifications off permanently.
 *
 * Three things are deliberately skipped:
 *
 *   - anybody who was already in the app today, from activity_days. Nudging
 *     somebody who wrote an hour ago is the fastest way to make the nudge
 *     worthless.
 *   - anybody who switched reminders off on their profile.
 *   - suspended accounts.
 */
class SendDailyReminders extends Command
{
    protected $signature = 'escalate:remind {--force : Send regardless of the hour, for testing}';

    protected $description = 'Send the daily reminder to devices where it is the chosen hour';

    public function handle(): int
    {
        if (! config('escalate.push.enabled')) {
            $this->info('Reminders are switched off.');

            return self::SUCCESS;
        }

        if (! Push::configured()) {
            $this->warn('No VAPID keys, so nothing can be sent. Set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY.');

            return self::SUCCESS;
        }

        $due = DueReminders::forHour(
            (int) config('escalate.push.hour'),
            ignoreClock: (bool) $this->option('force'),
        );

        if ($due->isEmpty()) {
            $this->info('Nothing due this hour.');

            return self::SUCCESS;
        }

        $result = Push::send(
            $due,
            (string) config('escalate.push.title'),
            (string) config('escalate.push.body'),
            route('today'),
        );

        $this->info("Sent {$result['sent']}, pruned {$result['pruned']}, failed {$result['failed']}.");

        return self::SUCCESS;
    }
}
