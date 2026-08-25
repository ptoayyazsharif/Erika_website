<?php

namespace App\Support;

use App\Models\Plan as PlanModel;
use Laravel\Cashier\Cashier;
use RuntimeException;
use Throwable;

/**
 * Creates the Stripe side of a plan from the plan itself.
 *
 * The point is that an administrator never opens the Stripe dashboard to sell
 * something. They type a name and an amount here; this makes the Product and
 * the Price over there and stores the id that comes back.
 *
 * THE ONE THING TO UNDERSTAND: a Stripe Price is immutable. You cannot change
 * what a price charges. Editing the amount therefore means creating a NEW price
 * and archiving the old one — which is not a workaround, it is the behaviour
 * you want. Everyone already subscribed stays on the price they agreed to until
 * they are deliberately moved, so raising the price does not silently raise it
 * for existing customers. That is both the decent thing and, for auto-renewal,
 * very often the legally required one.
 *
 * Nothing here ever deletes. Old prices are archived (`active => false`), which
 * leaves them working for anyone still on them and merely hides them from
 * anything new.
 *
 * Writes only happen for the mode currently selected, and only when there is
 * something to do. Saving a plan whose amount has not changed makes no API call
 * at all.
 */
class StripeSync
{
    /**
     * Bring the current Stripe mode into line with this plan.
     *
     * @return string|null a sentence about what changed, or null if nothing did
     *
     * @throws RuntimeException with a message fit to show an administrator
     */
    public static function plan(PlanModel $plan): ?string
    {
        if ($plan->isFree()) {
            return null;                       // nothing to sell
        }

        if (blank($plan->amount) || blank($plan->interval)) {
            return null;                       // not priced here; the id fields still work by hand
        }

        $mode = Stripe::mode();
        $creds = Stripe::credentials();

        if (blank($creds['secret'])) {
            throw new RuntimeException("No {$mode} secret key is set, so the plan could not be created in Stripe. The plan is saved; add the key and save again.");
        }

        if (! Stripe::keyLooksLikeMode($creds['secret'], $mode)) {
            throw new RuntimeException("The {$mode} secret key does not look like a {$mode} key. Nothing was sent to Stripe.");
        }

        $client = Cashier::stripe(['api_key' => $creds['secret']]);
        $currency = strtolower($plan->currency ?: config('escalate.billing.currency', 'usd'));

        $productField = $mode === Stripe::TEST ? 'stripe_product_test' : 'stripe_product';
        $priceField = $mode === Stripe::TEST ? 'stripe_price_test' : 'stripe_price';

        try {
            /* ── the product, created once and reused ─────────────────────── */

            $productId = $plan->{$productField};

            if (filled($productId)) {
                try {
                    $client->products->update($productId, [
                        'name' => $plan->label,
                        'description' => $plan->blurb ?: null,
                    ]);
                } catch (Throwable) {
                    // It was deleted in the dashboard, or belongs to another
                    // account. Make a fresh one rather than failing the save.
                    $productId = null;
                }
            }

            if (blank($productId)) {
                $productId = $client->products->create([
                    'name' => $plan->label,
                    'description' => $plan->blurb ?: null,
                    'metadata' => ['escalate_plan' => $plan->key],
                ])->id;
            }

            /* ── does the existing price already say the right thing ──────── */

            $existingId = $plan->{$priceField};

            if (filled($existingId)) {
                try {
                    $price = $client->prices->retrieve($existingId);

                    $matches = $price->active
                        && (int) $price->unit_amount === (int) $plan->amount
                        && $price->currency === $currency
                        && ($price->recurring->interval ?? null) === $plan->interval;

                    if ($matches) {
                        $plan->forceFill([$productField => $productId])->save();

                        return null;           // already correct; no API write
                    }
                } catch (Throwable) {
                    $existingId = null;        // gone from Stripe; make a new one
                }
            }

            /* ── a new price, because prices cannot be edited ─────────────── */

            $new = $client->prices->create([
                'product' => $productId,
                'unit_amount' => (int) $plan->amount,
                'currency' => $currency,
                'recurring' => ['interval' => $plan->interval],
                'metadata' => ['escalate_plan' => $plan->key],
            ]);

            $plan->forceFill([$productField => $productId, $priceField => $new->id])->save();

            // Archive rather than delete: anyone still on the old price keeps
            // paying what they agreed to, and it simply stops being offered.
            if (filled($existingId)) {
                try {
                    $client->prices->update($existingId, ['active' => false]);
                } catch (Throwable) {
                    // Not fatal — the plan already points at the new price.
                }

                return "Created a new {$mode} price in Stripe and archived the old one. "
                    .'Anyone already subscribed stays on the price they agreed to until you move them.';
            }

            return "Created the {$mode} product and price in Stripe.";
        } catch (Throwable $e) {
            throw new RuntimeException(self::explain($e, $mode));
        }
    }

    private static function explain(Throwable $e, string $mode): string
    {
        $m = $e->getMessage();

        return match (true) {
            str_contains($m, 'Invalid API Key') => "Stripe rejected the {$mode} key. The plan is saved here, but nothing was created there.",
            str_contains($m, 'permission'),
            str_contains($m, 'insufficient') => 'The key lacks a permission this needs. A restricted key must have Products (write) and Prices (write) to create plans from here.',
            str_contains($m, 'Connection'),
            str_contains($m, 'Could not resolve') => 'Could not reach Stripe from the server. The plan is saved here; save it again once the network is back.',
            default => 'Stripe refused: '.\Illuminate\Support\Str::limit($m, 160),
        };
    }
}
