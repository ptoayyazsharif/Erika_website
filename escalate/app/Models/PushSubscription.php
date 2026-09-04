<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * Every device that may receive a broadcast.
     *
     * Deliberately NOT App\Support\DueReminders, which is the other selection
     * in this app and looks close enough to reuse. It excludes anybody who was
     * already in the app today, which is right for a daily nudge — nudging
     * somebody who wrote an hour ago makes the nudge worthless — and wrong
     * here. An announcement is news: somebody who wrote this morning still
     * needs to hear that the beta ends on Friday.
     *
     * There is a test asserting exactly that difference, so a later tidy-up
     * that merges the two selections fails rather than quietly dropping the
     * most engaged testers from every announcement.
     *
     * Consent is the same switch either way: profiles.push_reminders is the
     * only push control a person has been given, and pushing to somebody who
     * turned it off would be ignoring it. Missing profile counts as opted in,
     * matching DueReminders and wantsAnnouncementEmails.
     */
    public function scopeReachable(Builder $query): void
    {
        $query
            ->whereHas('user', fn ($q) => $q->whereNull('suspended_at'))
            ->where(fn ($q) => $q
                ->whereHas('user.profile', fn ($p) => $p->where('push_reminders', true))
                ->orWhereDoesntHave('user.profile'));
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
