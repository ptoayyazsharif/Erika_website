<?php

namespace App\Support;

use App\Models\PushSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which devices should be nudged this hour.
 *
 * Separate from the command that sends, because the interesting part is the
 * choosing and the only way to reach it through the command is to actually
 * send — which needs a real push service. This way the rules can be asserted
 * on their own, which is where the mistakes would be.
 *
 * Three deliberate exclusions:
 *
 *   - anybody already in the app today. Nudging somebody who wrote an hour ago
 *     is the fastest way to make the nudge worthless.
 *   - anybody who switched reminders off.
 *   - suspended accounts.
 */
class DueReminders
{
    /** @return Collection<int, PushSubscription> */
    public static function forHour(int $hour, bool $ignoreClock = false): Collection
    {
        $today = now()->toDateString();

        $activeToday = DB::table('activity_days')
            ->where('day', $today)
            ->pluck('user_id')
            ->flip();

        return PushSubscription::query()
            ->with('user.profile')
            ->whereHas('user', fn ($q) => $q->whereNull('suspended_at'))
            ->get()
            ->filter(fn (PushSubscription $s) => $ignoreClock || self::isTheHourThere($s, $hour))
            ->filter(fn (PushSubscription $s) => (bool) ($s->user?->profile?->push_reminders ?? true))
            ->reject(fn (PushSubscription $s) => $activeToday->has($s->user_id))
            ->values();
    }

    /** Is it the chosen hour where this device is? */
    private static function isTheHourThere(PushSubscription $subscription, int $hour): bool
    {
        // A device that never reported a zone falls back to the app's own,
        // which is better than never reminding it at all.
        $zone = $subscription->timezone ?: config('app.timezone');

        try {
            return Carbon::now($zone)->hour === $hour;
        } catch (\Throwable) {
            // A zone that no longer exists must not stop everybody else's
            // reminder: this fails closed for one device and carries on.
            return false;
        }
    }
}
