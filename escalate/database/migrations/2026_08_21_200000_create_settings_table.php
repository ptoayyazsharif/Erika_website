<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings an administrator can change without a redeploy.
 *
 * These OVERLAY the config files rather than replacing them: a key absent from
 * this table falls through to config/escalate.php and therefore to the
 * environment. That ordering matters in both directions — a fresh install works
 * with an empty table, and deleting a row is a real "revert to what the server
 * was deployed with" rather than a way to blank a setting by accident.
 *
 * `value` is encrypted for every row, not only the API keys. Two reasons: the
 * cast is uniform so no future setting can be added to the wrong column by
 * mistake, and the rows nobody thinks of as secret — a Stripe price id, a
 * model name — still describe the account they belong to.
 *
 * `is_secret` is about DISPLAY, not storage. A secret value is never rendered
 * back to a browser, not even to the administrator who typed it: the form shows
 * the last four characters and takes a replacement or nothing. An admin session
 * is exactly the session most worth stealing, and a settings page that prints
 * live API keys turns one stolen session into two stolen vendor accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // The config path this overrides, e.g. 'escalate.quotas.stories_per_day'.
            // Validated against an explicit allowlist in App\Support\Settings —
            // never taken from a request, or an admin form becomes a way to set
            // app.key or database.connections.*.
            $table->string('key', 120)->unique();

            $table->text('value')->nullable();
            $table->boolean('is_secret')->default(false);

            // Who changed it last, for the times someone asks why the quota moved.
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            /*
             * An administrator's answer to "put this person on the full plan".
             *
             * Deliberately separate from the Stripe subscription rather than
             * faking one: comping a friend, a beta tester or a refund case must
             * not involve writing rows that look like a payment nobody made.
             * Plan::for() checks this first and says so.
             *
             * Not fillable on the model — privilege is never assignable from a
             * request, the same rule `role` follows.
             */
            $table->string('plan_override', 40)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan_override');
        });

        Schema::dropIfExists('settings');
    }
};
