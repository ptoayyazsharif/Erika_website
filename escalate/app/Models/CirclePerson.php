<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A person in My Circle. Names are the most identifying thing here, so all three fields are encrypted. */
class CirclePerson extends Model
{
    protected $fillable = ['name', 'relationship', 'note', 'position'];

    protected function casts(): array
    {
        return [
            'name'         => 'encrypted',
            'relationship' => 'encrypted',
            'note'         => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
