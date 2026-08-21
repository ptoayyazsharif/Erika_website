<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans, moved out of config so an administrator can change them.
 *
 * They started in config/escalate.php, which was right while they were a
 * developer's concern and wrong the moment someone wanted to add a tier without
 * a deploy. The config array stays as the seed and as the fallback for an
 * install whose table is empty, so nothing breaks on the way across.
 *
 * TWO price columns, and this is the part that is easy to get wrong: a Stripe
 * price id is scoped to the mode it was made in. `price_abc` in test mode does
 * not exist in live mode and vice versa. One column would mean flipping the
 * test switch silently pointed checkout at ids the active keys cannot resolve —
 * failing at the worst moment, in front of a customer, with a Stripe error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Stable identifier. `users.plan_override` and every entitlement
            // check store this, so renaming one is a data migration, not a
            // rename — hence a separate `label` for what people actually read.
            $table->string('key', 40)->unique();

            $table->string('label', 60);
            $table->string('blurb', 200)->nullable();

            $table->string('stripe_price', 120)->nullable();       // live mode
            $table->string('stripe_price_test', 120)->nullable();  // test mode
            $table->string('display', 60)->nullable();
            $table->string('interval', 16)->nullable();            // month|year|null

            // Per-day allowances. JSON because the set of generation kinds is
            // the app's business, not the schema's — adding one should not need
            // a migration on a table an administrator is editing.
            $table->json('quotas')->nullable();

            $table->unsignedSmallInteger('position')->default(0);

            // A retired plan stays here rather than being deleted: existing
            // subscribers still resolve through it, and their entitlement must
            // not silently drop because a tier was withdrawn from the picker.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
