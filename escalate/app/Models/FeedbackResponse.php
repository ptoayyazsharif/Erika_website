<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's answers to the day-seven survey.
 *
 * Nothing is mass-assignable, for the reason Application gives: every value
 * arrives from a form, and `disappointment` is the number the whole beta is
 * judged on.
 *
 * These answers are content, and an administrator may read them — which is only
 * true because they were written *to* that administrator. A gratitude entry was
 * not, and no admin screen will ever show one.
 */
class FeedbackResponse extends Model
{
    public const VERY     = 'very';
    public const SOMEWHAT = 'somewhat';
    public const NOT      = 'not';

    /** The answers, in the order they are asked. */
    public const FEELINGS = [
        self::VERY     => 'Very disappointed',
        self::SOMEWHAT => 'Somewhat disappointed',
        self::NOT      => 'Not disappointed',
    ];

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'who_for' => 'encrypted',
            'benefit' => 'encrypted',
            'improve' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feeling(): string
    {
        return self::FEELINGS[$this->disappointment] ?? $this->disappointment;
    }

    /**
     * The only answer that is a number.
     *
     * Forty per cent "very disappointed" is the line the original survey draws
     * between a product people would miss and one they would not.
     */
    public function isVeryDisappointed(): bool
    {
        return $this->disappointment === self::VERY;
    }
}
