<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a plan carry its own price, so Stripe can be filled in FROM here.
 *
 * Until now an administrator had to create the product and price in Stripe by
 * hand, copy the price id back, and paste it into the plan — for each of test
 * and live. Two systems, four copy-pastes, and every one of them a chance to
 * paste a live id into the test box.
 *
 * With an amount and an interval on the plan, the app can create the Stripe
 * Product and Price itself and store the id it gets back.
 *
 * `amount` is in MINOR UNITS — cents, pence — as an integer, because that is
 * what Stripe uses and because money in a float is how rounding errors get
 * into invoices. £12.00 is 1200.
 *
 * Product and price ids are per mode for the same reason they always were:
 * Stripe's test and live worlds share nothing, so an object created in one does
 * not exist in the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('amount')->nullable()->after('blurb');
            $table->string('currency', 3)->nullable()->after('amount');

            // The Stripe Product a plan's prices hang off. Created once and
            // reused, so changing an amount does not litter the dashboard with
            // a new product each time.
            $table->string('stripe_product', 120)->nullable()->after('currency');
            $table->string('stripe_product_test', 120)->nullable()->after('stripe_product');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['amount', 'currency', 'stripe_product', 'stripe_product_test']);
        });
    }
};
