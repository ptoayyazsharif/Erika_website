<?php

namespace App\Models;

use App\Support\SafeMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
            'send_push'    => 'boolean',
            'published_at' => 'datetime',
            'emailed_at'   => 'datetime',
            'pushed_at'    => 'datetime',
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

    public function wasPushed(): bool
    {
        return $this->pushed_at !== null;
    }

    /** The body as HTML, with an admin's markup rendered inert. */
    public function html(): HtmlString
    {
        return SafeMarkdown::render((string) $this->body);
    }

    /**
     * The body as one or two lines of plain text, for a notification.
     *
     * ── Why an announcement may say what a reminder may not ─────────────────
     *
     * App\Support\Push states that a notification carries nothing private,
     * because a lock screen is readable by whoever is near the phone. That rule
     * has not been relaxed here — it has been applied to different content.
     * The daily reminder would have to quote somebody's own journal to say
     * anything; an announcement is written by an administrator and sent to
     * every user, so it is already known to the whole beta by construction and
     * there is nothing left for a passer-by to learn.
     *
     * Written down because the obvious "fix" later is to replace this with a
     * generic string for safety, which would make every announcement arrive as
     * a notification saying nothing and being tapped by nobody.
     *
     * The stored body is Markdown, which is not notification text: rendered,
     * stripped of tags, entity-decoded (so &amp;amp; is not what buzzes), the
     * whitespace markdown leaves behind collapsed, and cut short. A phone
     * truncates anyway, and it does it mid-word.
     */
    public function notificationBody(int $limit = 140): string
    {
        $text = strip_tags((string) $this->html());
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($text, $limit, '…');
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
