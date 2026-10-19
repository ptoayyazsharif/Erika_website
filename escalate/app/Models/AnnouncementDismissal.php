<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row: this person closed that banner. */
class AnnouncementDismissal extends Model
{
    public $timestamps = false;

    protected $fillable = [];
}
