<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * My World — one row per user.
 *
 * Free-text answers are encrypted; preference keys are not. See the schema
 * migration for why that split exists.
 */
class Profile extends Model
{
    protected $fillable = [
        'preferred_name', 'city', 'life_context', 'values', 'anchor',
        'voice', 'story_style', 'faith_language', 'perspective', 'tone',
        'default_length', 'onboarded',
    ];

    protected function casts(): array
    {
        return [
            'preferred_name' => 'encrypted',
            'city'           => 'encrypted',
            'life_context'   => 'encrypted',
            'anchor'         => 'encrypted',
            'values'         => 'encrypted:array',
            'onboarded'      => 'boolean',
            'affirmations_generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The provider voice id. Read server-side only — never sent to a browser. */
    public function voiceId(): string
    {
        return config("escalate.voices.{$this->voice}.id")
            ?? config('escalate.voices.still.id');
    }
}
