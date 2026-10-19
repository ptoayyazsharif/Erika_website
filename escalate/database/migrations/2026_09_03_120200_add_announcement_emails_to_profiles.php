<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether somebody wants announcement emails.
 *
 * On the profile, where the rest of a person's settings already live, rather
 * than in a new preferences table — Stage D's push opt-out will read the same
 * row instead of inventing a second home for the same idea.
 *
 * Default true, because an announcement is the point of joining a beta. It only
 * ever covers announcements: an invite, a password reset or a confirmation is a
 * reply to something the person did, and must still reach somebody who opted
 * out of the newsletter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('announcement_emails')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('announcement_emails');
        });
    }
};
