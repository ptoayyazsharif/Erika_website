<?php

namespace App\Support;

use App\Models\AiEvent;
use App\Models\User;

/**
 * Per-user spend limits.
 *
 * Counted from the ai_events ledger rather than from a counter column, so the
 * number can never drift out of step with what was actually spent — and so a
 * failed call that still cost money still counts against the limit.
 *
 * Only successful calls count. A provider outage should not eat someone's
 * daily allowance.
 */
class Quota
{
    public static function used(User $user, string $kind): int
    {
        $completed = AiEvent::where('user_id', $user->id)
            ->where('kind', $kind)
            ->where('ok', true)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $completed + self::inFlight($user, $kind);
    }

    /**
     * Work that has been paid for but has not landed in the ledger yet.
     *
     * Without this the quota is check-then-spend across a queue boundary. On
     * shared hosting the worker runs from cron, so it can be minutes behind —
     * and every request in that window sees used = 0. The route throttle allows
     * twelve story POSTs an hour against a limit of five, so a burst of clicks
     * queues twelve jobs that all passed the check, and each job may call the
     * provider more than once. The bill is the user's clicking, not the limit.
     *
     * Counting queued and in-progress rows closes the window.
     */
    private static function inFlight(User $user, string $kind): int
    {
        return match ($kind) {
            // No date filter. It used to bound these to the last 24 hours by
            // created_at, which is when the ROW was made, not when the work was
            // queued — so regenerating a story older than a day was invisible
            // to the very counter that exists to stop a burst. queued/writing
            // is already a small bounded set; a stale row there is a stuck job
            // worth counting anyway.
            'story' => $user->stories()
                ->whereIn('state', ['queued', 'writing'])
                ->count(),
            'narration' => $user->narrations()
                ->whereIn('state', ['queued', 'rendering'])
                ->count(),
            // Rewinds were missing here, so the check-then-spend window the two
            // above close was wide open on the one route that shares their
            // shape: rewinds.generate is throttled at twelve an hour against a
            // limit of three a day, and WriteRewind re-checks this same counter
            // — which could not see its own queued row. Twelve presses on a
            // backed-up queue therefore queued twelve paid writes, all of which
            // passed both checks.
            'rewind' => $user->rewinds()
                ->whereIn('state', ['queued', 'writing'])
                ->count(),
            default => 0,
        };
    }

    public static function limit(string $kind): int
    {
        return match ($kind) {
            'story'        => (int) config('escalate.quotas.stories_per_day'),
            'narration'    => (int) config('escalate.quotas.narrations_per_day'),
            'affirmations' => (int) config('escalate.quotas.affirmations_per_day'),
            'rewind'       => (int) config('escalate.quotas.rewinds_per_day'),
            default        => 0,
        };
    }

    public static function remaining(User $user, string $kind): int
    {
        return max(0, self::limit($kind) - self::used($user, $kind));
    }

    public static function allows(User $user, string $kind): bool
    {
        return self::remaining($user, $kind) > 0;
    }

    /** A sentence to show the user when they have run out. */
    public static function message(string $kind): string
    {
        return match ($kind) {
            'story'     => 'You have used today’s readings. More tomorrow — a reading is worth returning to more than it is worth replacing.',
            'narration' => 'You have used today’s narrations. The words are still here to read.',
            'rewind'    => 'You have used today’s rewinds.',
            default     => 'You have reached today’s limit for this.',
        };
    }
}
