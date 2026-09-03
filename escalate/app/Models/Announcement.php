<?php

namespace App\Models;

use App\Support\SafeMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

/**
 * Something an administrator wants everybody to know.
 *
 * $fillable is empty and everything is written with forceFill, matching
 * Application and for the same reason: `published_at` and `send_email` decide
 * who sees this and whose inbox it reaches, and a mass-assignable field that
 * decides either would be one crafted field name away from a mistake nobody
 * could take back.
 */
class Announcement extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'show_in_app'  => 'boolean',
            'send_email'   => 'boolean',
            'published_at' => 'datetime',
            'emailed_at'   => 'datetime',
        ];
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function wasEmailed(): bool
    {
        return $this->emailed_at !== null;
    }

    /** The body as HTML, with an admin's markup rendered inert. */
    public function html(): HtmlString
    {
        return SafeMarkdown::render((string) $this->body);
    }

    /* ── queries ─────────────────────────────────────────────────────────── */

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * The one banner to show this person, if any.
     *
     * Newest published, in-app announcement they have not closed. One query,
     * on every signed-in page render, so it is a `whereNotExists` rather than
     * loading dismissals and filtering in PHP.
     */
    public static function bannerFor(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        return self::query()
            ->published()
            ->where('show_in_app', true)
            ->whereNotExists(fn ($q) => $q
                ->selectRaw(1)
                ->from('announcement_dismissals')
                ->whereColumn('announcement_dismissals.announcement_id', 'announcements.id')
                ->where('announcement_dismissals.user_id', $user->id))
            ->latest('published_at')
            ->first();
    }
}
