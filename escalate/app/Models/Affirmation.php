<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affirmation extends Model
{
    protected $fillable = ['body', 'back', 'position', 'favourite', 'desire_id'];

    protected function casts(): array
    {
        return [
            'body'      => 'encrypted',
            'back'      => 'encrypted',
            'favourite' => 'boolean',
        ];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(AffirmationSet::class, 'affirmation_set_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function desire(): BelongsTo
    {
        return $this->belongsTo(Desire::class);
    }
}
