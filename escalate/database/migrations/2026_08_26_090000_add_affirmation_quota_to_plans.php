<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Give the plans already in the table an affirmation allowance.
 *
 * Plan::quota() reads `$plan['quotas'][$kind] ?? 0`, so a plan row written
 * before affirmations existed answers zero — and with billing switched on that
 * is not "unlimited", it is "nobody may draw a card". The config defaults were
 * updated in the same change, but those only apply to a fresh install; every
 * deployment that has already seeded its plans needs this.
 *
 * Existing values are left alone. An administrator who has already tuned a
 * plan should not have it rewritten by a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        // Matches the config defaults: one a day free, two on a paid plan.
        $defaults = ['free' => 1, 'monthly' => 2, 'yearly' => 2];

        foreach (Plan::all() as $plan) {
            $quotas = $plan->quotas ?? [];

            if (array_key_exists('affirmation', $quotas)) {
                continue;
            }

            // An unrecognised plan key gets the paid allowance rather than
            // zero: a custom plan somebody added should not silently lose a
            // feature the moment it ships.
            $quotas['affirmation'] = $defaults[$plan->key] ?? 2;

            $plan->forceFill(['quotas' => $quotas])->save();
        }

        \App\Support\Plan::flush();
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        foreach (Plan::all() as $plan) {
            $quotas = $plan->quotas ?? [];
            unset($quotas['affirmation']);
            $plan->forceFill(['quotas' => $quotas])->save();
        }

        \App\Support\Plan::flush();
    }
};
