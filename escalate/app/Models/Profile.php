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
        'voice', 'theme', 'story_style', 'faith_language', 'perspective', 'tone',
        'default_length', 'onboarded', 'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_name' => 'encrypted',
            'city'           => 'encrypted',
            'life_context'   => 'encrypted',
            'anchor'         => 'encrypted',
            // Religious or philosophical belief — special category under GDPR
            // Art. 9. "none" is equally revealing, so there is no value of this
            // field that is safe to leave in the clear.
            'faith_language' => 'encrypted',
            'values'         => 'encrypted:array',
            'onboarded'      => 'boolean',

            // Cast but deliberately NOT fillable. It is a consent flag, and the
            // My World form mass-assigns this model — a field name added to
            // that request should not be able to opt somebody back in to
            // email. The unsubscribe route sets it explicitly.
            'announcement_emails' => 'boolean',
            'push_reminders'      => 'boolean',
            'consented_at'   => 'datetime',
            'affirmations_generated_at' => 'datetime',
        ];
    }

    /**
     * Defaults for a fresh profile.
     *
     * faith_language used to be varchar(32) DEFAULT 'none'. Encrypting it meant
     * dropping and recreating the column as text, and an encrypted column
     * cannot carry a meaningful database default — the default would have to be
     * ciphertext, tied to one APP_KEY. So the default moves into the model,
     * where it belongs anyway.
     */
    protected static function booted(): void
    {
        static::creating(function (Profile $profile) {
            $profile->faith_language ??= 'none';
        });
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
