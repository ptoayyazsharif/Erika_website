<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per outbound AI call. Deliberately holds no user content — only what
 * was called, whether it worked, and what it cost. This is the table the admin
 * area reports on, which is why it is safe for an admin to read.
 */
class AiEvent extends Model
{
    protected $fillable = [
        'user_id', 'kind', 'provider', 'model', 'ok', 'error_code',
        'input_tokens', 'output_tokens', 'characters', 'duration_ms', 'cost_microcents',
    ];

    protected function casts(): array
    {
        return ['ok' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function costCents(): float
    {
        return round(($this->cost_microcents ?? 0) / 10000, 4);
    }
}
