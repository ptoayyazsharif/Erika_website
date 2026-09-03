<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Something to tell everybody.
 *
 * There was no channel at all: an administrator could reply to one person's
 * application, or reach nobody. "The beta ends Friday" had nowhere to go.
 *
 * One row, two destinations, chosen per message — a banner in the app, an email,
 * or both. `emailed_at` is the guard that makes Send idempotent: pressing it
 * twice is the failure with no undo, and a nullable timestamp is a cheaper way
 * to prevent it than anything clever.
 *
 * ── Why the body is not encrypted ───────────────────────────────────────────
 *
 * Everything a person writes in this app is encrypted at rest. This is the
 * deliberate exception, and it is not an oversight: an announcement is written
 * by an administrator and broadcast to every user. Encrypting it would protect
 * it from nobody — the audience is everybody — while making it unreadable to a
 * queue worker rendering a hundred emails. The rule exists to protect a
 * journal, and this is a notice board.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');

            $table->boolean('show_in_app')->default(true);
            $table->boolean('send_email')->default(false);

            // Null means a draft: written but reaching nobody.
            $table->timestamp('published_at')->nullable();

            // Set before the first job is dispatched, and checked first, so a
            // second press cannot mail a hundred people twice.
            $table->timestamp('emailed_at')->nullable();

            // nullOnDelete: an announcement outlives the admin who wrote it,
            // and deleting a colleague's account should not delete the notice
            // everybody has already read.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
