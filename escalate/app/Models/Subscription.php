<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Cashier's subscription, plus the columns this app added.
 *
 * It exists for one reason: Cashier declares its own $casts, so a date column
 * added by a migration comes back as a string and ->format() fatals on the
 * billing page. Extending is cheaper and safer than patching the package.
 */
class Subscription extends CashierSubscription
{
    protected $casts = [
        'quantity'           => 'integer',
        'trial_ends_at'      => 'datetime',
        'ends_at'            => 'datetime',
        'current_period_end' => 'datetime',
    ];
}
