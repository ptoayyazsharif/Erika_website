<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt `profiles.faith_language`.
 *
 * It was grouped with voice and tone as "preferences, not confidences", which
 * was a reasonable-sounding call and the wrong one. Religious or philosophical
 * belief is special-category data under GDPR Art. 9 — the highest bar there is —
 * and it sat in plaintext next to the user's name and email. Note that "none"
 * is equally revealing: a secular position is a philosophical belief.
 *
 * Done as add / copy / drop / rename rather than change(), because the column is
 * varchar(32) and a ciphertext envelope is ~200 characters. SQLite would not
 * complain; MySQL would silently truncate and every value would become
 * undecryptable. Reversible, and the down() path decrypts rather than dropping.
 *
 * Nothing queries this column in SQL — it is only ever read into
 * StoryWriter::faithRule() — so making it unindexable costs nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->text('faith_language_enc')->nullable()->after('faith_language');
        });

        foreach (DB::table('profiles')->select('id', 'faith_language')->cursor() as $row) {
            DB::table('profiles')
                ->where('id', $row->id)
                ->update([
                    // Crypt::encryptString is exactly what the `encrypted` cast
                    // does: encrypt($value, serialize: false).
                    'faith_language_enc' => Crypt::encryptString((string) ($row->faith_language ?: 'none')),
                ]);
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('faith_language');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->renameColumn('faith_language_enc', 'faith_language');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('faith_language_plain', 32)->default('none')->after('faith_language');
        });

        foreach (DB::table('profiles')->select('id', 'faith_language')->cursor() as $row) {
            $plain = 'none';

            try {
                $plain = Crypt::decryptString((string) $row->faith_language);
            } catch (\Throwable) {
                // Already plaintext, or encrypted under a key we no longer hold.
                // Falling back to the default is better than failing the
                // rollback and leaving the table half-migrated.
            }

            DB::table('profiles')->where('id', $row->id)->update(['faith_language_plain' => $plain]);
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('faith_language');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->renameColumn('faith_language_plain', 'faith_language');
        });
    }
};
