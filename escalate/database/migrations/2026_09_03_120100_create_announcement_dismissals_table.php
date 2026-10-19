<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who has closed which banner.
 *
 * cascadeOnDelete on both sides, the same as activity_days: deleting an
 * account takes its dismissals with it, so AccountEraser needs no new code for
 * erasure.
 *
 * It is deliberately NOT in the data export. "You closed a banner on the 3rd"
 * is interface state, not something anybody would recognise as their own data,
 * and padding an export with it makes the things that matter harder to find.
 * The preference that does matter — whether they want announcement emails —
 * lives on the profile and is exported with the rest of their settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            // The uniqueness is the mechanism, not a safety net — dismissing
            // twice from two tabs must not make two rows.
            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_dismissals');
    }
};
