<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether somebody wants the daily nudge.
 *
 * Beside announcement_emails, which Stage C put on the profile for exactly
 * this — one home for a person's preferences rather than a table each.
 *
 * Separate from announcement_emails on purpose: wanting a quiet daily reminder
 * on your own phone and wanting marketing email are different appetites, and
 * one switch for both would make somebody choose between them.
 *
 * Default true, but that grants nothing on its own — a browser permission
 * prompt still has to be accepted, so push is opt-in by construction. This flag
 * is how somebody turns it off again without revoking permission at the browser
 * level, which most people do not know how to undo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('push_reminders')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('push_reminders');
        });
    }
};
