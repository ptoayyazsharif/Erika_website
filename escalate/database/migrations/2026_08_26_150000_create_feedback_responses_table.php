<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The day-seven survey.
 *
 * The Sean Ellis product-market-fit set, which is where Erika's killer question
 * comes from. Asking it in its original form means the score can be compared to
 * every other product that has ever run it, rather than only to itself.
 *
 * `disappointment` is plaintext because it is the scored answer: the whole
 * measure is what share said "very", and that has to be groupable in SQL. The
 * three prose answers are encrypted like everything else a person writes here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();

            // One response per person. Unique rather than checked, so two taps
            // cannot make two rows.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('disappointment', 24);   // very | somewhat | not
            $table->text('who_for')->nullable();
            $table->text('benefit')->nullable();
            $table->text('improve')->nullable();

            $table->timestamps();

            $table->index('disappointment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
    }
};
