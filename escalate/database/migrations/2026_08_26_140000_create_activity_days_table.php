<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which days somebody was here.
 *
 * `users.last_login_at` is one timestamp, overwritten on every sign-in. It can
 * say when a person was last seen and nothing whatever about the shape of their
 * week — so "did they come back the next day" and "still engaged at day
 * fourteen", the two questions a beta exists to answer, were unanswerable.
 *
 * One row per person per day. No content, no times, no paths: it records that
 * somebody opened the app on a date, which is the least that can answer the
 * question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');

            // The uniqueness is the mechanism, not a safety net: the middleware
            // inserts and ignores, so the index is what makes "once a day" true
            // rather than a check that two overlapping requests could both pass.
            $table->unique(['user_id', 'day']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_days');
    }
};
