<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A device that agreed to be reminded.
 *
 * $fillable is empty and everything is written with forceFill, as everywhere
 * else here: the endpoint and keys arrive from the browser, and an endpoint is
 * a URL this server will later make a request to. A mass-assignable one is a
 * field name away from being pointed somewhere it should not go.
 */
class PushSubscription extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The hash the unique index is built on.
     *
     * Endpoints run past 200 characters, which is longer than an indexable
     * string column on MySQL — so uniqueness is enforced on a hash of the URL
     * rather than the URL itself.
     */
    public static function hash(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}
