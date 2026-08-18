<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person in My Circle.
 *
 * Names are the most identifying thing this app stores, so every field is
 * encrypted — including the notes, which are the ones most likely to say
 * something the person would never want read.
 */
class CirclePerson extends Model
{
    protected $fillable = ['name', 'relationship', 'note', 'notes', 'position'];

    protected function casts(): array
    {
        return [
            'name'         => 'encrypted',
            'relationship' => 'encrypted',
            'note'         => 'encrypted',
            // 'encrypted:array' rather than 'encrypted' + json_decode: the cast
            // handles the serialisation, so nothing downstream has to know the
            // column is ciphertext.
            'notes'        => 'encrypted:array',
        ];
    }

    /**
     * Every detail recorded about this person, oldest first.
     *
     * Reads through the retired single `note` column too, so a row written
     * before notes existed still has its detail even if the migration has not
     * been run against that database yet.
     */
    public function details(): array
    {
        $notes = array_values(array_filter($this->notes ?? [], 'filled'));

        if ($notes === [] && filled($this->note)) {
            return [$this->note];
        }

        return $notes;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
