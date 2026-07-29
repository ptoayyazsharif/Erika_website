<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A quarterly reflection. One per user per quarter. */
class Reflection extends Model
{
    protected $fillable = ['year', 'quarter'];

    protected function casts(): array
    {
        return [
            'summary'        => 'encrypted',
            'shifts'         => 'encrypted',
            'relationships'  => 'encrypted',
            'lessons'        => 'encrypted',
            'failure_reason' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->state === 'ready' && filled($this->summary);
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'writing'], true);
    }

    public function label(): string
    {
        return "Q{$this->quarter} {$this->year}";
    }

    /** The date range this reflection covers. */
    public function range(): array
    {
        $start = now()->setDate($this->year, ($this->quarter - 1) * 3 + 1, 1)->startOfDay();

        return [$start, $start->copy()->addMonths(3)->subDay()->endOfDay()];
    }
}
