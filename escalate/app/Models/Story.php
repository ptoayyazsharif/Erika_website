<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    protected $fillable = ['title', 'body', 'edited_body', 'favourite'];

    protected function casts(): array
    {
        return [
            'title'          => 'encrypted',
            'body'           => 'encrypted',
            'edited_body'    => 'encrypted',
            'failure_reason' => 'encrypted',
            'favourite'      => 'boolean',
            'last_played_at' => 'datetime',
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

    public function narrations(): HasMany
    {
        return $this->hasMany(Narration::class);
    }

    public function narrationFor(string $voice): ?Narration
    {
        return $this->narrations()->where('voice', $voice)->first();
    }

    /* ── text ────────────────────────────────────────────────────────────── */

    /**
     * What the user should read and hear. An edit always wins over the
     * generated text — once someone has changed a word, that is their story.
     */
    public function text(): string
    {
        return trim((string) ($this->edited_body ?: $this->body));
    }

    public function isEdited(): bool
    {
        return filled($this->edited_body) && $this->edited_body !== $this->body;
    }

    public function isReady(): bool
    {
        return $this->state === 'ready' && filled($this->text());
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'writing'], true);
    }

    /** Paragraphs, for the line-by-line reveal. */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\n\s*\n/', $this->text()) ?: [],
        )));
    }

    public function words(): int
    {
        return str_word_count($this->text());
    }

    /** Roughly how long the narration runs, for showing before audio exists. */
    public function estimatedSeconds(): int
    {
        return (int) round($this->words() / 2.6);
    }
}
