<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An announcement's third destination.
 *
 * It already had two — a banner in the app and an email — and Stage D built
 * push for the daily reminder without ever wiring the two together. So an
 * administrator could tell everybody something in two places and not on the one
 * surface a phone actually shows them.
 *
 * The columns mirror send_email / emailed_at exactly, for the same reasons:
 * the boolean is an intention recorded when the announcement is written, and
 * the timestamp is the guard that makes the send idempotent. `pushed_at` is set
 * before anything is dispatched and checked first, because a notification is
 * the one destination here with no undo at all — an email at least sits in an
 * inbox looking like a duplicate, a second buzz is a second interruption.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('send_push')->default(false)->after('send_email');
            $table->timestamp('pushed_at')->nullable()->after('emailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['send_push', 'pushed_at']);
        });
    }
};
