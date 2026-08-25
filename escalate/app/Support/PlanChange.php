<?php

namespace App\Support;

use App\Models\Plan as PlanModel;
use App\Models\User;

/**
 * Whether moving between two plans is an upgrade or a downgrade — and it
 * decides when the change takes effect, which is the whole point.
 *
 * The rule every subscription business converges on, for reasons that are
 * about money rather than taste:
 *
 *   UPGRADE   — apply now, charge the difference now. The customer asked for
 *               more and should have it immediately.
 *
 *   DOWNGRADE — apply at the end of the period they have already paid for.
 *               Nothing charged, nothing refunded, nothing taken away early.
 *
 * Doing a downgrade immediately is the trap. Stripe's proration does not
 * refund the unused remainder, it parks it as account credit — so a customer
 * eleven months into a year who moves to monthly hands over their annual plan
 * and gets a credit balance instead of money. They read that as being charged
 * twice and having their plan taken away, and they are not entirely wrong.
 *
 * It also makes the annual price pointless: if a year's commitment can be
 * abandoned in a click with credit back, the discount for committing has
 * bought nothing.
 *
 * Ranking is tier first, then term — not price.
 *
 * Price alone cannot tell these apart. Annualised, the yearly plan is the
 * CHEAPER one, which is the entire reason anybody buys it; rank by that and
 * monthly-to-yearly reads as a downgrade. Rank by the amount on the invoice
 * instead and a $99/year plan outranks a $50/month plan that is worth six
 * times as much over a year.
 *
 * So: what you get decides first, and only when two plans give exactly the
 * same — as monthly and yearly do here — does the length of the commitment
 * break the tie, with the longer one being the upgrade. That is both what
 * customers mean by the words and what makes the annual discount coherent.
 */
class PlanChange
{
    public const UPGRADE   = 'upgrade';
    public const DOWNGRADE = 'downgrade';
    public const SAME      = 'same';

    public static function direction(?string $from, string $to): string
    {
        if ($from === $to) {
            return self::SAME;
        }

        $fromTier = self::tier($from);
        $toTier   = self::tier($to);

        if ($toTier !== $fromTier) {
            return $toTier > $fromTier ? self::UPGRADE : self::DOWNGRADE;
        }

        // Same entitlement: the longer commitment is the upgrade.
        $fromTerm = self::term($from);
        $toTerm   = self::term($to);

        if ($toTerm === $fromTerm) {
            return self::SAME;
        }

        return $toTerm > $fromTerm ? self::UPGRADE : self::DOWNGRADE;
    }

    /**
     * What a plan gives, as one comparable number.
     *
     * The daily allowances added together. Crude, but it is the only thing
     * that distinguishes these plans from one another, and it keeps working
     * when a third tier is added: a plan that grants more outranks one that
     * grants less, whatever either of them costs.
     */
    public static function tier(?string $key): int
    {
        if ($key === null || $key === Plan::FREE) {
            return 0;
        }

        $quotas = Plan::config($key)['quotas'] ?? [];

        return (int) array_sum(array_map('intval', $quotas));
    }

    /** How long a commitment is, in days, for breaking a tier tie. */
    public static function term(?string $key): int
    {
        if ($key === null || $key === Plan::FREE) {
            return 0;
        }

        return match (PlanModel::where('key', $key)->value('interval')) {
            'day'   => 1,
            'week'  => 7,
            'month' => 30,
            'year'  => 365,
            default => 0,
        };
    }

    /** What the button on a plan card should say, given where they are. */
    public static function label(User $user, string $key): string
    {
        $current = $user->planKey();

        if ($current === Plan::FREE) {
            return 'Choose this plan';
        }

        return match (self::direction($current, $key)) {
            self::UPGRADE   => 'Upgrade to this',
            self::DOWNGRADE => 'Switch at renewal',
            default         => 'Choose this plan',
        };
    }
}
