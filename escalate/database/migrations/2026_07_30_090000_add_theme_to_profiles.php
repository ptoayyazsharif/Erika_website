<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Stored server-side rather than only in localStorage so the choice
            // follows the user to a new device — and so the server can render
            // it into <html data-theme>, which is what removes the flash of the
            // wrong theme on first paint. A CSP with script-src 'self' forbids
            // the usual inline <head> script that would otherwise fix it.
            $table->string('theme', 32)->default('midnight')->after('voice');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
