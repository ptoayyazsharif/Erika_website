<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invites — the door to a closed beta.
 *
 * `code` is plaintext on purpose, which is worth defending because every other
 * secret in this app is not.
 *
 * A hashed code could never be shown again, and the whole point of this table
 * is that somebody reads a code off a terminal and sends it to a person by
 * hand. It is also a low-value capability: it buys the ability to create an
 * account, which behind it is still guarded by a password, a quota and — once
 * the beta is over — nothing at all. Compare that with the journal itself,
 * which is why everything in `desires` and `stories` is ciphertext and this is
 * not.
 *
 * Single use is enforced by the claim being a conditional UPDATE rather than a
 * read-then-write; see Invite::claim(). Two people pasting the same code at the
 * same moment is exactly the case a read-then-write loses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();

            // Unique so a generated collision fails loudly at insert rather
            // than quietly handing two people the same door.
            $table->string('code', 32)->unique();

            // Optional. When set, the invite only works for this address — so a
            // forwarded code cannot be used by whoever it was forwarded to.
            $table->string('email')->nullable();

            // Free text for whoever is handing these out: "Erika's friend Maya".
            // Never shown to the person being invited.
            $table->string('note', 120)->nullable();

            // nullOnDelete rather than cascade: if the invited account is later
            // deleted, the invite stays claimed. Freeing it would silently
            // hand a used code back to whoever still has it in an email.
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
