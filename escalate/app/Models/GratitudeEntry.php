<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A gratitude entry.
 *
 * The body is encrypted, so the brief's "searchable history" cannot be a SQL
 * LIKE. Search happens in PHP after decryption (see GratitudeController), which
 * is fine at journal scale — a heavy user writes a few thousand entries, not a
 * few million. Tags get a plaintext index because filtering by them is the
 * difference between a usable archive and a scroll.
 */
class GratitudeEntry extends Model
{
    // `desire_id` excluded for the same reason as on Affirmation: the FK has no
    // ownership constraint, so it must be set only after checking the desire.
    protected $fillable = ['body', 'tags', 'for_date'];

    protected function casts(): array
    {
        return [
            'body'     => 'encrypted',
            'tags'     => 'encrypted:array',
            'for_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function desire(): BelongsTo
    {
        return $this->belongsTo(Desire::class);
    }

    /**
     * Keeps `tags` and `tag_index` in step, and clean, on every save.
     *
     * The cleaning happens here rather than only in the controller because the
     * two columns must never disagree. A blank string surviving in `tags`
     * renders as an empty chip in the archive — a bare circle with no label —
     * while `tag_index` correctly drops it, so the entry appears to carry a tag
     * that no filter can ever match.
     */
    protected static function booted(): void
    {
        static::saving(function (GratitudeEntry $entry) {
            $tags = collect($entry->tags ?? [])
                ->map(fn ($t) => trim((string) $t))
                ->filter()
                ->unique()
                ->values();

            $entry->tags = $tags->all();

            $normalised = $tags
                ->map(fn ($t) => Str::of($t)->lower()->replaceMatches('/[^a-z0-9 ]/', '')->squish()->value())
                ->filter()
                ->unique()
                ->values();

            $entry->tag_index = $normalised->isEmpty() ? null : '|'.$normalised->implode('|').'|';
        });
    }

    public function matches(string $needle): bool
    {
        $needle = Str::lower(trim($needle));

        if ($needle === '') {
            return true;
        }

        return Str::contains(Str::lower((string) $this->body), $needle)
            || Str::contains((string) $this->tag_index, $needle);
    }
}
