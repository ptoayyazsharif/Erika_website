<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Support\Carbon;

/**
 * Where a selected applicant actually got to.
 *
 * Selecting somebody mints an invite and emails a code, and then nothing
 * reports on what happened next. An application reads "selected" whether the
 * person signed up an hour later or never opened the email, and telling those
 * apart meant reading three screens and joining them by hand.
 *
 * The chain is already in the database and needs no new columns:
 *
 *     application.invite_id → invite.claimant → user → stories, activity_days
 *
 * Every step is derived from a fact recorded because something else needed it.
 * That is deliberately not a status column: a column would be a second copy of
 * the truth, free to drift from it the first time somebody signs up through a
 * path nobody remembered to update.
 *
 * The facts arrive as arguments rather than being fetched here. On a list of
 * every tester, a class that queried per person would be one query per row per
 * table — the N+1 that Admin\BetaController already avoids by grouping.
 */
class TesterStatus
{
    /** In order. Each implies the ones before it. */
    public const REVOKED   = 'revoked';
    public const EXPIRED   = 'expired';
    public const INVITED   = 'invited';
    public const SIGNED_UP = 'signed_up';
    public const WRITING   = 'writing';
    public const ACTIVE    = 'active';

    /** Somebody counts as active if they were here inside this window. */
    public const ACTIVE_DAYS = 7;

    public static function of(Application $application, ?Carbon $lastActive, bool $hasStory): string
    {
        $invite = $application->invite;

        // select() always mints an invite inside its transaction, so a selected
        // application without one was revoked — the row cannot merely be absent.
        if (! $invite) {
            return self::REVOKED;
        }

        if (! $invite->claimant) {
            return $invite->isExpired() ? self::EXPIRED : self::INVITED;
        }

        // Active beats writing: somebody who came back this week is doing the
        // thing, whether or not they have added a story since.
        if ($lastActive && $lastActive->gte(now()->subDays(self::ACTIVE_DAYS)->startOfDay())) {
            return self::ACTIVE;
        }

        return $hasStory ? self::WRITING : self::SIGNED_UP;
    }

    /** Plain words: this screen is read by somebody deciding about a person. */
    public static function label(string $status): string
    {
        return match ($status) {
            self::REVOKED   => 'Access revoked',
            self::EXPIRED   => 'Invite expired',
            self::INVITED   => 'Invited, never signed up',
            self::SIGNED_UP => 'Signed up, nothing written',
            self::WRITING   => 'Written, quiet lately',
            self::ACTIVE    => 'Active this week',
            default         => $status,
        };
    }

    /** Which ones want a second look. Drives the ordering on the screen. */
    public static function needsAttention(string $status): bool
    {
        return in_array($status, [self::INVITED, self::EXPIRED, self::SIGNED_UP], true);
    }

    /**
     * Only somebody who never claimed their invite can be revoked.
     *
     * Revoking after they have signed up would leave an account behind with no
     * invite explaining where it came from. Suspending is the tool for that,
     * and it already exists on the user's own screen.
     */
    public static function isRevocable(string $status): bool
    {
        return in_array($status, [self::INVITED, self::EXPIRED], true);
    }
}
