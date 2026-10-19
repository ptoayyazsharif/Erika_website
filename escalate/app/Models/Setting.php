<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One config override.
 *
 * Nothing is mass-assignable: the key is checked against an allowlist before it
 * ever reaches here (see App\Support\Settings), and letting a request fill it
 * would be handing over the allowlist's whole purpose.
 */
class Setting extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'value'     => 'encrypted',
            'is_secret' => 'boolean',
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
