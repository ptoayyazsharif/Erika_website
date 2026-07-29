<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An image attached to a desire. `path` points at the private disk; the file is
 * only ever served through MediaController, never by the web server.
 */
class DesireImage extends Model
{
    protected $fillable = ['path', 'role', 'bytes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function desire(): BelongsTo
    {
        return $this->belongsTo(Desire::class);
    }
}
