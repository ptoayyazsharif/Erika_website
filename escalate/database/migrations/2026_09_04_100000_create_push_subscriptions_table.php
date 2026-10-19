<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One device that has agreed to be reminded.
 *
 * A row per device, not per person: the same somebody on a phone and a laptop
 * is two subscriptions with two endpoints, and revoking one must not silence
 * the other.
 *
 * `timezone` is carried here rather than on the user because it belongs to the
 * device. The browser reports its own on subscribe, and the hourly command
 * sends to whoever is currently at the chosen hour *locally* — so a reminder
 * arrives at nine in the morning wherever somebody is, instead of at three
 * because the server is elsewhere. A notification at 3am is how somebody turns
 * notifications off for good.
 *
 * The endpoint is unique. A browser re-subscribing the same device hands back
 * the same URL, and without the constraint every reinstall would leave a
 * duplicate that gets its own copy of every reminder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Push endpoints are long — Chrome's run past 200 characters — so
            // this is a text column with a hashed index rather than a string
            // with a unique index, which MySQL would refuse at that length.
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();

            $table->string('p256dh');
            $table->string('auth');

            // Null when the browser did not report one, which is what the
            // fallback in the command is for.
            $table->string('timezone')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
