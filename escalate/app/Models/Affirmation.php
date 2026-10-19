<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affirmation extends Model
{
    /*
    | `desire_id` is not fillable. The foreign key carries no ownership
    | constraint, so a fillable desire_id would let a user attach their own
    | affirmation to somebody else's desire. Assign it after checking the
    | desire belongs to them.
    */
    protected $fillable = ['body', 'back', 'position', 'favourite'];

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
