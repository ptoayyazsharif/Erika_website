<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A day's worth of affirmation cards. One set per user per date. */
class AffirmationSet extends Model
{
    // `state` is server-controlled: a client must not be able to POST state=ready.
    protected $fillable = ['for_date'];

    protected function casts(): array
    {
        return [
            'for_date'       => 'date',
            'failure_reason' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function affirmations(): HasMany
    {
        return $this->hasMany(Affirmation::class)->orderBy('position');
    }

    public function isReady(): bool
    {
        return $this->state === 'ready';
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'writing'], true);
    }
}
