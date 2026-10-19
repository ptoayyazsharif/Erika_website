<?php

namespace App\Support;

/**
 * Which Stripe account the app is talking to, and with which keys.
 *
 * Stripe keeps two entirely separate worlds. Test and live have their own keys,
 * their own webhook signing secrets, their own customers, and their own price
 * ids — an id minted in one is meaningless in the other. So "test mode" cannot
 * be a single boolean laid over one set of credentials; it has to select a
 * whole set, and everything downstream has to follow it.
 *
 * The mode and both key sets live in settings, so switching is a toggle in the
 * admin panel rather than a redeploy. This class is the one place that resolves
 * which set is live, and Settings::apply() copies the result into cashier.*
 * so Cashier itself needs no knowledge of any of it.
 */
class Stripe
{
    public const LIVE = 'live';
    public const TEST = 'test';

    public static function mode(): string
    {
        return config('escalate.stripe.mode') === self::TEST ? self::TEST : self::LIVE;
    }

    public static function isTest(): bool
    {
        return self::mode() === self::TEST;
    }

    /** The credentials for the active mode. */
    public static function credentials(): array
    {
        $set = config('escalate.stripe.'.self::mode(), []);

        return [
            'key'            => $set['key'] ?? null,
            'secret'         => $set['secret'] ?? null,
            'webhook_secret' => $set['webhook_secret'] ?? null,
        ];
    }

    /**
     * Copy the active set into the keys Cashier reads.
     *
     * Called from Settings::apply(), after the stored overrides are in place —
     * so the mode toggle and the keys it selects are both already current.
     */
    public static function apply(): void
    {
        $c = self::credentials();

        config([
            'cashier.key'            => $c['key'],
            'cashier.secret'         => $c['secret'],
            'cashier.webhook.secret' => $c['webhook_secret'],
        ]);
    }

    /**
     * Whether the active mode is actually usable.
     *
     * Used by the admin panel to say so plainly, and by the plan picker to
     * avoid offering a button that cannot work. A half-configured mode is the
     * likeliest state during setup and the worst one to discover from a
     * customer.
     */
    public static function configured(): bool
    {
        $c = self::credentials();

        return filled($c['key']) && filled($c['secret']);
    }

    /** Test keys are unmistakable, so a live key pasted in the test box is caught. */
    public static function keyLooksLikeMode(?string $key, string $mode): bool
    {
        if (blank($key)) {
            return true;
        }

        $isTestKey = str_contains($key, '_test_');

        return $mode === self::TEST ? $isTestKey : ! $isTestKey;
    }
}
