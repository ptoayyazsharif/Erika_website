<?php

namespace App\Support;

use App\Models\Plan as PlanModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Which plan somebody is on, and what that entitles them to.
 *
 * The only place in the app that turns a subscription into a number.
 * Everything else — Quota, the billing screen, the paywall message — asks here,
 * so "what does a paying user get" has one answer rather than one per screen.
 *
 * Definitions now come from the `plans` table (App\Models\Plan) so an
 * administrator can edit them, falling back to config/escalate.php when that
 * table is empty or absent. Entitlement itself is still read from the local
 * subscriptions table Cashier keeps in step via webhooks — never by calling
 * Stripe. A generation request must not depend on a third party being
 * reachable, and a Stripe outage should slow nothing down here.
 */
class Plan
{
    public const FREE = 'free';

    private const CACHE_KEY = 'escalate.plans';

    /**
     * Every plan, keyed as before so existing call sites are unchanged.
     *
     * Shaped exactly like the old config array — label, blurb, price, display,
     * interval, quotas — because half a dozen views read those keys. `price` is
     * resolved for the ACTIVE Stripe mode, so nothing downstream has to know
     * test mode exists.
     */
    public static function all(): array
    {
        $rows = self::rows();

        if ($rows === []) {
            return config('escalate.plans', []);
        }

        return $rows;
    }

    /** @return array<string, array> plan definitions from the database */
    private static function rows(): array
    {
        try {
            $plans = Cache::rememberForever(self::CACHE_KEY.'.'.Stripe::mode(), function () {
                if (! Schema::hasTable('plans')) {
                    return [];
                }

                return PlanModel::orderBy('position')->orderBy('id')->get()
                    ->mapWithKeys(fn (PlanModel $p) => [$p->key => [
                        'label'     => $p->label,
                        'blurb'     => $p->blurb,
                        'price'     => $p->priceId(),
                        'display'   => $p->priceLabel(),
                        'interval'  => $p->interval,
                        'quotas'    => $p->quotas ?? [],
                        'is_active' => $p->is_active,
                    ]])->all();
            });

            return is_array($plans) ? $plans : [];
        } catch (\Throwable $e) {
            // Same reasoning as Settings::stored(): this is read during a
            // request that may be serving a page, and a cache or database
            // fault must degrade to the deployed config rather than 500.
            report($e);

            return [];
        }
    }

    public static function flush(): void
    {
        foreach ([Stripe::LIVE, Stripe::TEST] as $mode) {
            try {
                Cache::forget(self::CACHE_KEY.'.'.$mode);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** Plans someone can actually buy: active, not free, and priced in this mode. */
    public static function purchasable(): array
    {
        return array_filter(
            self::all(),
            fn ($plan, $key) => $key !== self::FREE
                && ($plan['is_active'] ?? true)
                && filled($plan['price'] ?? null),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function config(string $key): array
    {
        return self::all()[$key] ?? self::all()[self::FREE] ?? [];
    }

    /** The plan key for a Stripe price id, or null if it is not one of ours. */
    public static function keyForPrice(?string $price): ?string
    {
        if (blank($price)) {
            return null;
        }

        foreach (self::all() as $key => $plan) {
            if (($plan['price'] ?? null) === $price) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The plan a user is on right now.
     *
     * Free unless billing is switched on AND they hold a subscription Cashier
     * considers valid. `subscribed()` counts an active trial and a subscription
     * inside its grace period after cancellation — both are cases where the
     * person has paid for access they still have, and taking it away early
     * because a cancel is pending would be theft of something they bought.
     */
    public static function for(User $user): string
    {
        if (! config('escalate.billing.enabled')) {
            return self::FREE;
        }

        /*
         * An administrator's override wins over Stripe.
         *
         * This is how someone is comped — a friend, a beta tester, a refund
         * case — and it deliberately does not fabricate a subscription row to
         * do it. A row that looks like a payment nobody made is the kind of
         * thing that later gets reconciled against Stripe and cannot be
         * explained. Checked first so it also works for someone who has never
         * had a subscription at all.
         */
        if (filled($user->plan_override) && array_key_exists($user->plan_override, self::all())) {
            return $user->plan_override;
        }

        if (! $user->subscribed()) {
            return self::FREE;
        }

        $price = $user->subscription()?->stripe_price;

        /*
         * A subscription whose price is no longer known — a plan renamed or
         * retired out from under a live subscriber, or a price minted in the
         * other Stripe mode — resolves to a paid plan rather than to free.
         * Silently demoting someone who is still being charged is the worse of
         * the two failures.
         */
        return self::keyForPrice($price)
            ?? array_key_first(self::purchasable())
            ?? self::FREE;
    }

    /**
     * The daily allowance for a kind of generation.
     *
     * With billing off this ignores plans entirely and returns the flat
     * 'quotas' numbers — so the app behaves exactly as it did before any of
     * this existed. That is what makes shipping the billing code safe while
     * the beta is still running.
     */
    public static function quota(User $user, string $kind): int
    {
        if (! config('escalate.billing.enabled')) {
            return self::flatQuota($kind);
        }

        $plan = self::config(self::for($user));

        return (int) ($plan['quotas'][$kind] ?? 0);
    }

    /** The pre-billing numbers, still the source of truth when billing is off. */
    private static function flatQuota(string $kind): int
    {
        return match ($kind) {
            'story'        => (int) config('escalate.quotas.stories_per_day'),
            'narration'    => (int) config('escalate.quotas.narrations_per_day'),
            'affirmations' => (int) config('escalate.quotas.affirmations_per_day'),
            'rewind'       => (int) config('escalate.quotas.rewinds_per_day'),
            default        => 0,
        };
    }

    /** True when upgrading would actually get this person more of $kind. */
    public static function upgradeWouldHelp(User $user, string $kind): bool
    {
        if (! config('escalate.billing.enabled') || self::for($user) !== self::FREE) {
            return false;
        }

        $best = collect(self::purchasable())
            ->map(fn ($plan) => (int) ($plan['quotas'][$kind] ?? 0))
            ->max() ?? 0;

        return $best > self::quota($user, $kind);
    }
}
