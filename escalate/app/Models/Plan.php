<?php

namespace App\Models;

use App\Support\Stripe;
use Illuminate\Database\Eloquent\Model;

/**
 * A plan, as an administrator edits it.
 *
 * App\Support\Plan is the read side — "what is this user entitled to" — and it
 * asks this model for the definitions. Keeping them apart means entitlement
 * logic never grows a save() and this never grows a subscription check.
 */
class Plan extends Model
{
    public const FREE = 'free';

    protected $fillable = [
        'key', 'label', 'blurb', 'stripe_price', 'stripe_price_test',
        'display', 'interval', 'quotas', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quotas'    => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The price id for whichever Stripe mode is switched on.
     *
     * Never read `stripe_price` directly for checkout. In test mode that column
     * holds a live id, which the test keys cannot resolve — the failure lands
     * on the customer at the moment they press Choose.
     */
    public function priceId(): ?string
    {
        return Stripe::isTest() ? $this->stripe_price_test : $this->stripe_price;
    }

    public function isFree(): bool
    {
        return $this->key === self::FREE;
    }

    /** Buyable in the current mode: active, not free, and has an id that works. */
    public function isPurchasable(): bool
    {
        return $this->is_active && ! $this->isFree() && filled($this->priceId());
    }

    public function quota(string $kind): int
    {
        return (int) ($this->quotas[$kind] ?? 0);
    }
}
