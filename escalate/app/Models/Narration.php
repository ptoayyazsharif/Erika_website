<?php

namespace App\Models;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A rendered narration.
 *
 * `path` is relative to the *private* disk. Nothing here is web-reachable:
 * public/ has no symlink to storage, and audio is streamed by MediaController
 * after an ownership check. If you ever find yourself reaching for
 * Storage::url() on this model, that is the bug.
 */
class Narration extends Model
{
    /**
     * Nothing is mass-assignable.
     *
     * Every field on a narration is derived by the server — the voice comes from
     * the profile, the path from a hash, the rest from the provider response.
     * No request payload should ever reach any of them, so the list is empty and
     * the writers below use forceFill.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return ['failure_reason' => 'encrypted'];
    }

    /** Create a queued narration for a story in a given voice. */
    public static function queueFor(Story $story, string $voice): self
    {
        // A query rather than firstOrNew: firstOrNew would fill() the lookup
        // attributes, and nothing on this model is mass-assignable.
        $narration = static::query()
            ->where('story_id', $story->id)
            ->where('voice', $voice)
            ->first() ?? new static;

        $narration->forceFill([
            'story_id'       => $story->id,
            'user_id'        => $story->user_id,
            'voice'          => $voice,
            'state'          => 'queued',
            'failure_reason' => null,
        ]);

        try {
            $narration->save();
        } catch (UniqueConstraintViolationException) {
            // Two concurrent narrate requests both miss the select above and
            // both insert; the table's unique(story_id, voice) rejects the
            // second. That is the index doing its job — re-read the winner
            // rather than 500 on a double-clicked button.
            return static::query()
                ->where('story_id', $story->id)
                ->where('voice', $voice)
                ->firstOrFail();
        }

        return $narration;
    }

    public function markRendering(string $hash): void
    {
        $this->forceFill(['state' => 'rendering', 'content_hash' => $hash])->save();
    }

    public function markReady(array $attributes): void
    {
        $this->forceFill(['state' => 'ready', 'failure_reason' => null, ...$attributes])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill(['state' => 'failed', 'failure_reason' => $reason])->save();
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->state === 'ready'
            && filled($this->path)
            && Storage::disk('private')->exists($this->path);
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'rendering'], true);
    }

    public function seconds(): int
    {
        return (int) round(($this->duration_ms ?? 0) / 1000);
    }

    /** Deletes the audio file along with the row. */
    protected static function booted(): void
    {
        static::deleting(function (Narration $narration) {
            if (blank($narration->path)) {
                return;
            }

            // NarrateStory deliberately points a second row at an existing
            // file when the content hash matches — that is the dedupe that
            // stops one user paying twice for identical text. Unlinking
            // unconditionally therefore destroyed audio a live narration was
            // still using: the survivor kept state 'ready' with a dangling
            // path, and re-narrating cost full price on the expensive provider.
            $shared = static::where('path', $narration->path)
                ->whereKeyNot($narration->getKey())
                ->exists();

            if (! $shared) {
                Storage::disk('private')->delete($narration->path);
            }
        });
    }
}
