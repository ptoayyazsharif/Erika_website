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
        return AiEvent::where('user_id', $user->id)
            ->where('kind', $kind)
            ->where('ok', true)
            ->where('created_at', '>=', now()->subDay())
            ->count();
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
