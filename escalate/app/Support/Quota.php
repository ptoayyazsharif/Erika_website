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
            // Same shape as the three above, and included from the start rather
            // than added after the same window is found open a second time.
            'affirmation' => $user->affirmationSets()
                ->whereIn('state', ['queued', 'writing'])
                ->count(),
            default => 0,
        };
    }

    /**
     * The daily allowance, which now depends on who is asking.
     *
     * It used to take only $kind, and the User argument is the whole point of
     * the change: an allowance is a property of a subscription, not of the
     * application. Plan::quota() still returns the flat configured numbers
     * while billing is switched off, so this is not a behaviour change until
     * somebody enables it.
     */
    public static function limit(User $user, string $kind): int
    {
        return Plan::quota($user, $kind);
    }

    public static function remaining(User $user, string $kind): int
    {
        return max(0, self::limit($user, $kind) - self::used($user, $kind));
    }

    public static function allows(User $user, string $kind): bool
    {
        return self::remaining($user, $kind) > 0;
    }

    /**
     * A sentence to show the user when they have run out.
     *
     * Two different sentences, because they are two different situations and
     * telling someone to "come back tomorrow" when the real answer is "this is
     * the free tier" wastes their time and hides the offer. The upgrade wording
     * only ever appears when upgrading would genuinely get them more of this
     * particular thing — never as a reflex, and never to someone already paying
     * for the largest plan, who really does just have to wait.
     */
    public static function message(User $user, string $kind): string
    {
        if (Plan::upgradeWouldHelp($user, $kind)) {
            return match ($kind) {
                'story'     => 'That’s your reading for today on the free plan. A full plan gets you more, or come back tomorrow — that’s free.',
                'narration' => 'That’s your audio for today on the free plan. The words are still here to read, and a full plan gets you more.',
                'rewind'    => 'That’s your Rewind for today on the free plan. Your answers are saved either way.',
                'affirmation' => 'That’s your cards for today on the free plan. Today’s are still here to read, and a full plan gets you more.',
                default     => 'That’s your limit for today on the free plan.',
            };
        }

        return match ($kind) {
            'story'     => 'You’ve used today’s readings. There are more tomorrow, and the ones you have are still here.',
            'narration' => 'You’ve used today’s audio. The words are still here to read.',
            'rewind'    => 'You’ve used today’s Rewinds.',
            'affirmation' => 'You’ve written today’s cards. They’re still here to read, and there are new ones tomorrow.',
            default     => 'You’ve reached today’s limit for this.',
        };
    }
}
