<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A Rewind — the signature feature. One per desire, created once the desire
 * reaches a terminal status.
 */
class Rewind extends Model
{
    protected $fillable = [
        'what_happened', 'turning_points', 'redirections',
        'people', 'lessons', 'gratitude',
    ];

    protected function casts(): array
    {
        return [
            'what_happened'  => 'encrypted',
            'turning_points' => 'encrypted',
            'redirections'   => 'encrypted',
            'people'         => 'encrypted',
            'lessons'        => 'encrypted',
            'gratitude'      => 'encrypted',
            'narrative'      => 'encrypted',
            'failure_reason' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function desire(): BelongsTo
    {
        return $this->belongsTo(Desire::class);
    }

    public function isReady(): bool
    {
        return $this->state === 'ready' && filled($this->narrative);
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'writing'], true);
    }

    /** The reflection answers the user has actually filled in. */
    public function answered(): array
    {
        $fields = [
            'what_happened'  => 'What happened',
            'turning_points' => 'The turning points',
            'redirections'   => 'Where it redirected',
            'people'         => 'Who was part of it',
            'lessons'        => 'What you learned',
            'gratitude'      => 'What you are grateful for',
        ];

        return collect($fields)
            ->map(fn ($label, $key) => ['label' => $label, 'value' => $this->{$key}])
            ->filter(fn ($row) => filled($row['value']))
            ->all();
    }

    protected static function booted(): void
    {
        static::deleting(function (Rewind $rewind) {
            foreach ([$rewind->before_image_path, $rewind->after_image_path] as $path) {
                if (filled($path)) {
                    Storage::disk('private')->delete($path);
                }
            }
        });
    }
}
