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
    /*
    | `path` is deliberately absent. Storage::disk('private')->path() does no
    | traversal normalisation, so a fillable `path` means the day an upload
    | controller is written as $desire->images()->create($validated) — the
    | natural shape — a user can POST path=../../../<anything>, own the row
    | legitimately, and have MediaController serve whatever the PHP user can
    | read. Set it with forceFill from a server-generated name instead.
    */
    protected $fillable = ['role'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function desire(): BelongsTo
    {
        return $this->belongsTo(Desire::class);
    }
}
