<?php

namespace App\Support;

use App\Models\Plan as PlanModel;
use Laravel\Cashier\Cashier;
use Throwable;

/**
 * A read-only health check against Stripe.
 *
 * Every call here RETRIEVES. Nothing is created, updated or deleted — no
 * customer, no subscription, no price, no charge — and nothing in this
 * application's database is written or read for anything but the price ids to
 * look up. Pressing the button in the admin panel cannot cost money and cannot
 * change state on either side.
 *
 * That constraint is the whole design. A "test my keys" button that creates a
 * throwaway customer to prove the key works leaves litter in a real Stripe
 * account, and the litter is indistinguishable from a real customer when
 * somebody reconciles the books later.
 *
 * What it is actually trying to catch, in order of how often it happens:
 *
 *   1. A live key pasted into the test box, or the reverse. Silent until a
 *      customer meets it.
 *   2. A price id from the other mode. Also silent until checkout, where it
 *      surfaces as a Stripe error in front of the customer.
 *   3. A restricted key missing a permission the app needs.
 *   4. No webhook secret, which means subscriptions never reach the app at all
 *      even though Stripe is perfectly happy.
 */
class StripeCheck
{
    /** @return array{ok:bool, mode:string, checks:array<int,array>} */
    public static function run(): array
    {
        $mode = Stripe::mode();
        $creds = Stripe::credentials();
        $checks = [];

        $add = function (string $name, string $state, string $detail) use (&$checks) {
            // state: pass | warn | fail
            $checks[] = ['name' => $name, 'state' => $state, 'detail' => $detail];
        };

        /* ── the key itself ──────────────────────────────────────────────── */

        if (blank($creds['secret'])) {
            $add('Secret key', 'fail', "No {$mode} secret key is set. Nothing else can be checked.");

            return ['ok' => false, 'mode' => $mode, 'checks' => $checks];
        }

        // Caught before any network call, because the error Stripe returns for
        // a mode mismatch is much less clear than saying it plainly.
        if (! Stripe::keyLooksLikeMode($creds['secret'], $mode)) {
            $add('Secret key', 'fail',
                $mode === Stripe::TEST
                    ? 'This looks like a LIVE key in the test box. A live key here can move real money.'
                    : 'This looks like a TEST key in the live box. Real customers would not be able to pay.');

            return ['ok' => false, 'mode' => $mode, 'checks' => $checks];
        }

        /*
         * Cashier's own factory, not a hand-rolled client.
         *
         * The first version of this built a StripeClient with an API version
         * hardcoded here, and that was wrong twice over. It was a date I picked
         * rather than one the SDK recognises, so Stripe rejected the call and
         * the check reported a perfectly good key as broken. And even had the
         * date been valid, the check would have been talking to a different API
         * version than the application does — so a pass would not have meant
         * what it claimed.
         *
         * Cashier pins Stripe\Util\ApiVersion::CURRENT, which ships with the
         * SDK and moves when the SDK is upgraded. Going through Cashier means
         * this check exercises the same client, the same version and the same
         * configuration as a real checkout.
         */
        $client = Cashier::stripe(['api_key' => $creds['secret']]);

        /* ── does it authenticate, and does it have Prices read ──────────── */

        try {
            $client->prices->all(['limit' => 1]);
            $add('Secret key', 'pass', "Authenticated against Stripe in {$mode} mode, and can read prices.");
        } catch (Throwable $e) {
            $add('Secret key', 'fail', self::explain($e));

            return ['ok' => false, 'mode' => $mode, 'checks' => $checks];
        }

        /* ── whose account is it ─────────────────────────────────────────── */

        try {
            $account = $client->accounts->retrieve();
            $name = $account->business_profile->name ?? $account->settings->dashboard->display_name ?? $account->id;
            $live = $account->charges_enabled ?? null;

            $add('Account', 'pass', "Connected to “{$name}” ({$account->id})."
                .($mode === Stripe::LIVE && $live === false
                    ? ' Charges are NOT enabled on this account yet — Stripe will refuse live payments until onboarding is finished.'
                    : ''));
        } catch (Throwable $e) {
            // A restricted key without Account read is a perfectly reasonable
            // setup, so this is a note rather than a failure.
            $add('Account', 'warn', 'The key works but cannot read the account record. '
                .'That is fine — it just means the restricted key has no Account permission.');
        }

        /* ── do the configured price ids actually exist in THIS mode ─────── */

        $plans = PlanModel::orderBy('position')->get()->reject->isFree();

        if ($plans->isEmpty()) {
            $add('Plans', 'warn', 'No paid plans are configured, so there is nothing to price.');
        }

        foreach ($plans as $plan) {
            $priceId = $plan->priceId();
            $label = "{$plan->label} ({$mode} price)";

            if (blank($priceId)) {
                $add($label, $plan->is_active ? 'warn' : 'pass', $plan->is_active
                    ? "No {$mode} price id, so this plan is hidden from the picker while in {$mode} mode."
                    : 'No price id, but the plan is switched off anyway.');

                continue;
            }

            try {
                $price = $client->prices->retrieve($priceId);
                $amount = $price->unit_amount === null
                    ? 'no fixed amount'
                    : number_format($price->unit_amount / 100, 2).' '.strtoupper($price->currency);
                $every = $price->recurring->interval ?? null;

                $notes = [];
                if (! $price->active) {
                    $notes[] = 'the price is ARCHIVED in Stripe, so checkout will refuse it';
                }
                if ($every && $plan->interval && $every !== $plan->interval) {
                    $notes[] = "Stripe bills this every {$every} but the plan says every {$plan->interval}";
                }

                $add($label, $notes ? 'fail' : 'pass',
                    "{$amount}".($every ? " every {$every}" : '').'. '
                    .($notes ? ucfirst(implode('; ', $notes)).'.' : 'Resolves cleanly.'));
            } catch (Throwable $e) {
                $add($label, 'fail', "{$priceId} — ".self::explain($e));
            }
        }

        /* ── the webhook, which is the quiet one ─────────────────────────── */

        $add('Webhook secret', blank($creds['webhook_secret']) ? 'fail' : 'pass',
            blank($creds['webhook_secret'])
                ? "No {$mode} webhook signing secret. The endpoint returns 403 to Stripe, so no subscription will ever reach this app — people would pay and nothing would change here."
                : "Set. Stripe's {$mode} events will be accepted if the secret matches the endpoint.");

        $add('Billing', config('escalate.billing.enabled') ? 'pass' : 'warn',
            config('escalate.billing.enabled')
                ? 'Billing is on, so plans decide what people are allowed.'
                : 'Billing is switched OFF, so nobody can buy anything and everyone gets the flat limits. Keys can still be verified.');

        return [
            'ok' => ! collect($checks)->contains(fn ($c) => $c['state'] === 'fail'),
            'mode' => $mode,
            'checks' => $checks,
        ];
    }

    /** Stripe's own message, trimmed to something an operator can act on. */
    private static function explain(Throwable $e): string
    {
        $m = $e->getMessage();

        return match (true) {
            str_contains($m, 'Invalid API Key')       => 'Stripe rejected the key as invalid. Check it was copied whole.',
            str_contains($m, 'Expired API Key')       => 'That key has been revoked in Stripe. Roll a new one.',
            str_contains($m, 'does not have the required permissions'),
            str_contains($m, 'insufficient')          => 'The key authenticates but lacks a permission this needs. If it is a restricted key, add Prices (read).',
            str_contains($m, 'No such price')         => 'No such price in this mode. A price id from the other mode will not resolve here.',
            // Reported as itself rather than as a bad key: the credentials are
            // fine and the fix is a composer update, not a new key.
            str_contains($m, 'API version'),
            str_contains($m, 'outdated')              => 'The key is fine — Stripe is refusing the API version this app pins. That comes from the Stripe SDK, so the fix is updating laravel/cashier, not the key. Stripe said: '.\Illuminate\Support\Str::limit($m, 140),
            str_contains($m, 'Could not resolve host'),
            str_contains($m, 'Connection')            => 'Could not reach Stripe from the server. That is a network problem, not a key problem.',
            default                                   => \Illuminate\Support\Str::limit($m, 180),
        };
    }
}
