<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People asking to be let into the private beta.
 *
 * The five answers are encrypted. They are not survey data — question one asks
 * what part of someone's life they are praying about, and people answer that
 * honestly when the form is asking on behalf of a journal. It gets the same
 * treatment as anything else they would write here.
 *
 * The email is NOT encrypted, deliberately: a unique index is what stops one
 * address applying fifty times, and an encrypted column cannot carry one
 * (every row encrypts to different ciphertext). The address is also the only
 * field we must be able to look up to send a decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            // The five questions, in Erika's order.
            $table->text('changing')->nullable();     // what they're working toward
            $table->text('practice')->nullable();     // do they journal/pray/affirm
            $table->text('tried_apps')->nullable();   // used a manifestation app before
            $table->text('will_use')->nullable();     // realistically 4+ times in 7 days
            $table->text('will_feedback')->nullable(); // candid feedback afterwards

            // pending | selected | waitlisted
            $table->string('status', 16)->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();

            // The invite handed out on selection, so the decision and the code
            // that came from it stay tied together.
            $table->foreignId('invite_id')->nullable()->constrained('invites')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
