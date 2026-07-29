<?php

namespace App\Models;

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
    protected $fillable = ['voice', 'state', 'content_hash'];

    protected function casts(): array
    {
        return ['failure_reason' => 'encrypted'];
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
            if (filled($narration->path)) {
                Storage::disk('private')->delete($narration->path);
            }
        });
    }
}
