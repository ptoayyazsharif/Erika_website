<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

/**
 * Carry the config plans across, once.
 *
 * Separate from the table migration so it is obvious this is data rather than
 * schema, and guarded on the table being empty so re-running it on an install
 * where an administrator has already edited plans cannot overwrite their work.
 *
 * The live price id goes in `stripe_price` and `stripe_price_test` is left
 * null: the ids in config were minted for whichever mode was in use, and
 * guessing which would be worse than leaving a blank the admin panel flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Plan::query()->exists()) {
            return;
        }

        $position = 0;

        foreach (config('escalate.plans', []) as $key => $plan) {
            Plan::create([
                'key'          => $key,
                'label'        => $plan['label'] ?? ucfirst($key),
                'blurb'        => $plan['blurb'] ?? null,
                'stripe_price' => $plan['price'] ?? null,
                'display'      => $plan['display'] ?? null,
                'interval'     => $plan['interval'] ?? null,
                'quotas'       => $plan['quotas'] ?? [],
                'position'     => $position++,
                'is_active'    => true,
            ]);
        }
    }

    public function down(): void
    {
        Plan::query()->delete();
    }
};
