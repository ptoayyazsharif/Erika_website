<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A request to join the private beta.
 *
 * The answers are encrypted for the reason given on the migration: question one
 * asks what part of someone's life they are praying about, and it is asked on
 * behalf of a private journal.
 *
 * Nothing is mass-assignable. Every field here arrives from an unauthenticated
 * public form, and `status` decides who gets in — an $fillable containing it
 * would be one crafted field name away from self-selection.
 */
class Application extends Model
{
    public const PENDING    = 'pending';
    public const SELECTED   = 'selected';
    public const WAITLISTED = 'waitlisted';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'changing'      => 'encrypted',
            'practice'      => 'encrypted',
            'tried_apps'    => 'encrypted',
            'will_use'      => 'encrypted',
            'will_feedback' => 'encrypted',
            'decided_at'    => 'datetime',
        ];
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(Invite::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /**
     * The five answers under the questions that produced them.
     *
     * Labels read from App\Support\Copy rather than being written out again
     * here, so rewording a question in the admin panel cannot leave an answer
     * sitting under the sentence it was never asked.
     *
     * A question edited after answers exist does re-label older answers. For a
     * beta of twenty-five that is worth accepting rather than versioning, and
     * the admin field says so.
     */
    public function answers(): array
    {
        return collect(\App\Support\Copy::labels())
            ->mapWithKeys(fn (string $question, string $field) => [$question => $this->$field])
            ->all();
    }

    /** Same normalisation the unique index relies on. */
    public static function normaliseEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
