<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The whole Escalate schema in one migration.
 *
 * A note on column types, because it drives a lot of decisions downstream:
 * every column holding something the user wrote or was told is `text`, not
 * `string`, and is cast to `encrypted` on the model. Laravel's encryption is
 * AES-256-GCM and produces base64 of a JSON envelope, so ciphertext runs about
 * 2.5× the plaintext and cannot be indexed, sorted, or matched with LIKE.
 *
 * That is the deliberate cost of a private journal. Consequences honoured
 * throughout the app:
 *   - search over gratitude entries happens in PHP after decryption, not in SQL
 *   - anything the database needs to filter or sort on (status, dates, mood
 *     keys, tags) stays plaintext, and is metadata rather than content
 *   - the admin area reads only those plaintext columns, so an administrator
 *     can support a user without reading their journal
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ── My World: one row per user ──────────────────────────────────── */
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->text('preferred_name')->nullable();
            $table->text('city')->nullable();
            $table->text('life_context')->nullable();      // "where you are right now"
            $table->text('values')->nullable();            // JSON array, encrypted
            $table->text('anchor')->nullable();            // a place/object that grounds them

            // Preferences. Plaintext: they are settings, not confidences, and
            // the generator reads them on every request.
            $table->string('voice', 32)->default('still');
            $table->string('story_style', 32)->default('cinematic');
            $table->string('faith_language', 32)->default('none');
            $table->string('perspective', 16)->default('first');
            $table->string('tone', 32)->default('grounded');
            $table->string('default_length', 16)->default('medium');

            $table->boolean('onboarded')->default(false);
            $table->timestamp('affirmations_generated_at')->nullable();
            $table->timestamps();
        });

        /* ── My Circle: the people who appear in stories ─────────────────── */
        Schema::create('circle_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->text('relationship')->nullable();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'position']);
        });

        /* ── Desires ─────────────────────────────────────────────────────── */
        Schema::create('desires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('title');
            $table->text('description')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->text('desired_feelings')->nullable();   // JSON array, encrypted
            $table->text('non_negotiables')->nullable();
            $table->text('people_involved')->nullable();    // JSON array of names, encrypted

            // Metadata — filterable, sortable, and safe for an admin to see.
            $table->string('category', 40)->default('other');
            $table->string('timeframe', 40)->default('this_year');
            $table->string('status', 24)->default('desired');
            $table->boolean('open_to_better')->default(true);
            $table->string('story_length', 16)->default('medium');
            $table->string('perspective', 16)->default('first');
            $table->string('tone', 32)->default('grounded');

            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        /* ── Stories ─────────────────────────────────────────────────────── */
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desire_id')->nullable()->constrained()->nullOnDelete();

            $table->text('title')->nullable();
            $table->text('body')->nullable();               // the reading itself
            $table->text('edited_body')->nullable();        // the user's own edit, if any

            $table->string('state', 24)->default('queued'); // queued|writing|ready|failed
            $table->text('failure_reason')->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedSmallInteger('regenerations')->default(0);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            $table->boolean('favourite')->default(false);
            $table->timestamp('last_played_at')->nullable();
            $table->unsignedInteger('play_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'state']);
        });

        /* ── Narration ───────────────────────────────────────────────────
           One row per (story, voice). The file lives on the private disk and
           is only ever reachable through an authorised controller. */
        Schema::create('narrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('voice', 32);
            $table->string('state', 24)->default('queued'); // queued|rendering|ready|failed
            $table->text('failure_reason')->nullable();

            // sha256 of the narrated text + voice + model. Two users who
            // somehow narrate identical text still get separate rows and
            // separate files — the hash exists to avoid re-billing one user
            // for text they already narrated, not to share audio between them.
            $table->string('content_hash', 64)->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('bytes')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('characters')->nullable();
            $table->string('model', 64)->nullable();
            $table->timestamps();

            $table->unique(['story_id', 'voice']);
            $table->index(['user_id', 'state']);
            $table->index('content_hash');
        });

        /* ── Affirmations ────────────────────────────────────────────────── */
        Schema::create('affirmation_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('for_date');
            $table->string('state', 24)->default('queued');
            $table->text('failure_reason')->nullable();
            $table->string('model', 64)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'for_date']);
        });

        Schema::create('affirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desire_id')->nullable()->constrained()->nullOnDelete();

            $table->text('body');
            $table->text('back')->nullable();               // the reverse of the card
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('favourite')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'favourite']);
        });

        /* ── Gratitude ───────────────────────────────────────────────────── */
        Schema::create('gratitude_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desire_id')->nullable()->constrained()->nullOnDelete();

            $table->text('body');
            $table->text('tags')->nullable();               // JSON array, encrypted
            // A lowercase, punctuation-stripped copy of tags for filtering.
            // Tags are user-chosen labels rather than confidences, and the
            // journal is unusable without being able to filter by them.
            $table->text('tag_index')->nullable();
            $table->date('for_date');
            $table->timestamps();

            $table->index(['user_id', 'for_date']);
        });

        /* ── Rewind ──────────────────────────────────────────────────────── */
        Schema::create('rewinds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desire_id')->constrained()->cascadeOnDelete();

            $table->text('what_happened')->nullable();
            $table->text('turning_points')->nullable();
            $table->text('redirections')->nullable();
            $table->text('people')->nullable();
            $table->text('lessons')->nullable();
            $table->text('gratitude')->nullable();

            $table->text('narrative')->nullable();          // AI retrospective
            $table->string('state', 24)->default('draft');  // draft|queued|writing|ready|failed
            $table->text('failure_reason')->nullable();
            $table->string('model', 64)->nullable();

            $table->string('before_image_path')->nullable();
            $table->string('after_image_path')->nullable();

            $table->timestamps();

            $table->unique('desire_id');
            $table->index(['user_id', 'created_at']);
        });

        /* ── Quarterly reflection ────────────────────────────────────────── */
        Schema::create('reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');         // 1-4

            $table->text('summary')->nullable();
            $table->text('shifts')->nullable();             // changing priorities
            $table->text('relationships')->nullable();
            $table->text('lessons')->nullable();

            // Plaintext counts. These are aggregates, not content, and the
            // journey timeline reads them without decrypting anything.
            $table->unsignedInteger('gratitude_count')->default(0);
            $table->unsignedInteger('manifested_count')->default(0);
            $table->unsignedInteger('story_count')->default(0);

            $table->string('state', 24)->default('queued');
            $table->text('failure_reason')->nullable();
            $table->string('model', 64)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'year', 'quarter']);
        });

        /* ── Desire images (0–2 per desire, per the brief) ────────────────── */
        Schema::create('desire_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desire_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('role', 16)->default('vision');  // vision|before|after
            $table->unsignedInteger('bytes')->nullable();
            $table->timestamps();

            $table->index(['desire_id', 'role']);
        });

        /* ── AI spend ledger ─────────────────────────────────────────────
           Every outbound AI call lands here, successful or not. This is what
           the quota check counts and what the admin area reports on. No user
           content in it — only what was called and what it cost. */
        Schema::create('ai_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('kind', 32);                     // story|narration|affirmations|rewind|reflection
            $table->string('provider', 32);
            $table->string('model', 64)->nullable();
            $table->boolean('ok')->default(true);
            $table->string('error_code', 64)->nullable();

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('characters')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('cost_microcents')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'kind', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach ([
            'ai_events', 'desire_images', 'reflections', 'rewinds',
            'gratitude_entries', 'affirmations', 'affirmation_sets',
            'narrations', 'stories', 'desires', 'circle_people', 'profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
