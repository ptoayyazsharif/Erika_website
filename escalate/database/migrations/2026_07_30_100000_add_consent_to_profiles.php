<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // When the user confirmed they had read the disclosure. Evidencing
            // consent means having a date, not a belief that a box was ticked.
            $table->timestamp('consented_at')->nullable()->after('onboarded');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('consented_at');
        });
    }
};
