<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A person in My Circle gets many details instead of exactly one.
 *
 * The original shape was a single `note` column, and the form rendered five
 * fixed slots — so a circle was capped at five people with one fact each, and
 * nothing about that was visible until you tried to add a sixth.
 *
 * That is wrong for what this app is. A circle is not a form to fill in once:
 * someone met this month becomes a friend, a partner, or someone you no longer
 * speak to, and the readings are only as specific as what the app knows about
 * the people in them. `relationship` stays a single field because it is
 * genuinely one value at a time — what they are to you *now* — and `notes`
 * becomes a list that grows.
 *
 * `note` is deliberately left in place and migrated into `notes` rather than
 * dropped, so this is reversible and no existing detail is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circle_people', function (Blueprint $table) {
            // Encrypted at rest like every other field on this row, so it is
            // text rather than json — the ciphertext is a string, and the
            // model casts it back to an array.
            $table->text('notes')->nullable()->after('note');
        });

        // Carry every existing single note across as a one-item list. Done in
        // PHP rather than SQL because both columns are encrypted, and the
        // database cannot read either one.
        foreach (\App\Models\CirclePerson::query()->whereNotNull('note')->cursor() as $person) {
            if (filled($person->note)) {
                $person->forceFill(['notes' => [$person->note]])->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('circle_people', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
