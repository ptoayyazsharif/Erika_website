<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the thing they are paying for next renews.
 *
 * Cashier can answer this — Subscription::currentPeriodEnd() — but only by
 * calling Stripe once per subscription item, every time. That is a network
 * round trip on a page render, and a page that cannot draw during a Stripe
 * outage. So the date is kept here, written by the webhook and by the
 * reconciler, and read locally like everything else about entitlement.
 *
 * Nullable because a subscription recorded before this column existed has no
 * date until its next webhook, and "we do not know yet" has to be sayable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('current_period_end')->nullable()->after('quantity');
            // What the plan will become at that date, when a downgrade is
            // waiting. Null means nothing is scheduled.
            $table->string('scheduled_price')->nullable()->after('current_period_end');
            $table->string('stripe_schedule_id')->nullable()->after('scheduled_price');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['current_period_end', 'scheduled_price', 'stripe_schedule_id']);
        });
    }
};
