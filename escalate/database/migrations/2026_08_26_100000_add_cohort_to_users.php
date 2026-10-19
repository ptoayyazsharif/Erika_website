<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which intake somebody came in on.
 *
 * "The Founding 25" is a promise made in the launch materials — a badge, and
 * pricing that does not change. Recording it on the user rather than reading
 * it back through the invite means the promise survives the invite being
 * withdrawn, the note being edited, or the beta being reorganised later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cohort', 40)->nullable()->after('plan_override');
            $table->index('cohort');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['cohort']);
            $table->dropColumn('cohort');
        });
    }
};
