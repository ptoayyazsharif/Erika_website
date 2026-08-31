<?php

namespace App\Support;

use App\Models\AffirmationSet;
use App\Models\AiEvent;
use App\Models\Narration;
use App\Models\Rewind;
use App\Models\Story;

/**
 * A whole-application daily limit on generation.
 *
 * Quota bounds what one person can spend. Nothing bounded what everyone could,
 * and those are different failures: the per-user limit is an allowance, and it
 * multiplies by the number of accounts. A hundred accounts that should not
 * exist is a hundred times the daily quota, and the first anyone would have
 * known about it is the provider invoice.
 *
 * So this is a circuit breaker rather than an allowance, and it is designed to
 * be read at a glance when it trips:
 *
 *   - Counts, not currency. A count is exact. Costing it would mean trusting a
 *     pricing table to be current, and a ceiling that silently drifts with a
 *     stale price is worse than no ceiling.
 *
 *   - Successful calls plus in-flight work, exactly as Quota counts them — see
 *     the note there about check-then-spend across the queue boundary. A
 *     provider outage therefore does not trip the ceiling and lock everybody
 *     out of an app that is already failing; that is what the route throttles
 *     are for.
 *
 *   - Checked in the controller AND in the job, again like Quota, because the
 *     controller check happens before the job is queued and a backed-up queue
 *     can let many requests past it.
 */
class Ceiling
{
    public static function limit(string $kind): int
    {
        return match ($kind) {
            'story'     => (int) config('escalate.ceiling.stories_per_day'),
            'narration' => (int) config('escalate.ceiling.narrations_per_day'),
            'rewind'    => (int) config('escalate.ceiling.rewinds_per_day'),
            'affirmation' => (int) config('escalate.ceiling.affirmations_per_day'),
            default     => 0,
        };
    }

    public static function used(string $kind): int
    {
        $completed = AiEvent::where('kind', $kind)
            ->where('ok', true)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $completed + self::inFlight($kind);
    }

    /** Queued and running work, across every user. */
    private static function inFlight(string $kind): int
    {
        return match ($kind) {
            'story'     => Story::whereIn('state', ['queued', 'writing'])->count(),
            'narration' => Narration::whereIn('state', ['queued', 'rendering'])->count(),
            'rewind'    => Rewind::whereIn('state', ['queued', 'writing'])->count(),
            'affirmation' => AffirmationSet::whereIn('state', ['queued', 'writing'])->count(),
            default     => 0,
        };
    }

    /**
     * A limit of zero means unlimited, not blocked.
     *
     * Stated explicitly because the alternative reading — zero meaning "none
     * allowed" — turns an unset or mistyped environment variable into a
     * silently dead app, and the symptom (every generation fails with a
     * message about a limit nobody set) is miserable to diagnose.
     */
    public static function allows(string $kind): bool
    {
        $limit = self::limit($kind);

        return $limit <= 0 || self::used($kind) < $limit;
    }

    public static function remaining(string $kind): int
    {
        $limit = self::limit($kind);

        return $limit <= 0 ? PHP_INT_MAX : max(0, $limit - self::used($kind));
    }

    /**
     * What the person waiting is told.
     *
     * It has to be obvious this is not their fault and not their quota — the
     * per-user message says "you have used today's readings", and showing that
     * to someone on their first reading of the day would be simply untrue.
     */
    public static function message(): string
    {
        return 'Escalate has reached its limit for today across everyone using it. '
            .'That is a safety valve on our side while the app is in testing, not '
            .'anything you did. Try again tomorrow.';
    }
}
