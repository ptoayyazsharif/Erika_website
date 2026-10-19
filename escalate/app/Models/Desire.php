<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Desire extends Model
{
    protected $fillable = [
        'title', 'description', 'why_it_matters', 'desired_feelings',
        'non_negotiables', 'people_involved', 'category', 'timeframe',
        'open_to_better', 'story_length', 'perspective', 'tone',
    ];

    protected function casts(): array
    {
        return [
            'title'             => 'encrypted',
            'description'       => 'encrypted',
            'why_it_matters'    => 'encrypted',
            'non_negotiables'   => 'encrypted',
            'desired_feelings'  => 'encrypted:array',
            'people_involved'   => 'encrypted:array',
            'open_to_better'    => 'boolean',
            'status_changed_at' => 'datetime',
            'completed_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class)->latest();
    }

    public function rewind(): HasOne
    {
        return $this->hasOne(Rewind::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DesireImage::class);
    }

    public function gratitudeEntries(): HasMany
    {
        return $this->hasMany(GratitudeEntry::class);
    }

    /* ── status ──────────────────────────────────────────────────────────── */

    /** True when this status is one that unlocks a Rewind. */
    public function isComplete(): bool
    {
        return (bool) config("escalate.statuses.{$this->status}.terminal", false);
    }

    public function statusLabel(): string
    {
        return status_label($this->status);
    }

    /**
     * Move to a new status, recording when. `completed_at` is set the first time
     * the desire reaches a terminal status and cleared if it leaves one, so
     * "how long did this take" stays truthful even if a user changes their mind.
     */
    public function moveTo(string $status): void
    {
        if (! array_key_exists($status, config('escalate.statuses'))) {
            return;
        }

        $this->status = $status;
        $this->status_changed_at = now();
        $this->completed_at = $this->isComplete() ? ($this->completed_at ?? now()) : null;
        $this->save();
    }

    /* ── scopes ──────────────────────────────────────────────────────────── */

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['desired', 'unfolding']);
    }

    public function scopeComplete(Builder $query): Builder
    {
        // `?? false` for the same reason isComplete() has a default: a status
        // added to config without a 'terminal' key should fall out of this
        // scope, not fatal every screen that filters by it.
        $terminal = array_keys(array_filter(
            config('escalate.statuses'),
            fn ($s) => $s['terminal'] ?? false,
        ));

        return $query->whereIn('status', $terminal);
    }
}
