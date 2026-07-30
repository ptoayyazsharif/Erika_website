<?php

namespace App\Http\Controllers;

use App\Models\GratitudeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The gratitude journal.
 *
 * Search is the awkward part and worth explaining. Entry bodies are encrypted at
 * rest, so "searchable history" cannot be a SQL LIKE — the ciphertext does not
 * contain the words. Filtering therefore happens in PHP after decryption.
 *
 * That is fine at journal scale and would not be at analytics scale: someone
 * writing three entries a day for five years has ~5,000 rows, which decrypt in
 * a few milliseconds. If this ever needs to scale past that, the answer is a
 * blind index (HMAC per token), not dropping the encryption.
 *
 * Tags are the exception — `tag_index` is plaintext, so tag filtering is a real
 * SQL query and stays fast regardless.
 */
class GratitudeController extends Controller
{
    /** Offered as one-tap chips. Users can still write their own. */
    public const SUGGESTED_TAGS = [
        'people', 'work', 'health', 'home', 'money', 'small things', 'progress',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $tag = trim((string) $request->query('tag', ''));

        $query = $user->gratitudeEntries()->with('desire');

        // Tag filtering is a real query — tag_index is plaintext for exactly
        // this reason. The pipes make it an exact-token match rather than a
        // substring one, so "work" does not also match "homework".
        if ($tag !== '') {
            $query->where('tag_index', 'like', '%|'.mb_strtolower($tag).'|%');
        }

        $entries = $query->get();

        // Text search, after decryption. See the class note.
        if ($search !== '') {
            $entries = $entries->filter(fn (GratitudeEntry $e) => $e->matches($search))->values();
        }

        return view('gratitude.index', [
            'today'    => $user->gratitudeEntries()->whereDate('for_date', today())->get(),
            'grouped'  => $this->groupByDay($entries),
            'total'    => $entries->count(),
            'search'   => $search,
            'tag'      => $tag,
            'tags'     => $this->tagsInUse($user),
            'streak'   => $this->streak($user),
            'week'     => $this->week($user),
            'desires'  => $user->desires()->active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'body'      => ['required', 'string', 'min:2', 'max:2000'],
            'tags'      => ['nullable', 'array', 'max:6'],
            'tags.*'    => ['string', 'max:30'],
            'desire_id' => ['nullable', 'integer'],
        ]);

        $entry = $user->gratitudeEntries()->make([
            'body'     => trim($data['body']),
            'tags'     => collect($data['tags'] ?? [])->map(fn ($t) => trim($t))->filter()->unique()->values()->all(),
            'for_date' => today(),
        ]);

        // desire_id is not mass-assignable: the foreign key carries no ownership
        // constraint, so linking is only allowed after proving the desire is
        // this user's. A posted id for someone else's desire is dropped, not
        // rejected — there is nothing to tell the user, and refusing would
        // confirm the row exists.
        $entry->forceFill([
            'desire_id' => $this->ownedDesireId($request, $data['desire_id'] ?? null),
        ])->save();

        return redirect()->route('gratitude.index')->with('status', 'Kept.');
    }

    public function update(Request $request, GratitudeEntry $entry): RedirectResponse
    {
        $this->mine($request, $entry);

        $data = $request->validate([
            'body'   => ['required', 'string', 'min:2', 'max:2000'],
            'tags'   => ['nullable', 'array', 'max:6'],
            'tags.*' => ['string', 'max:30'],
        ]);

        $entry->update([
            'body' => trim($data['body']),
            'tags' => collect($data['tags'] ?? [])->map(fn ($t) => trim($t))->filter()->unique()->values()->all(),
        ]);

        return redirect()->route('gratitude.index')->with('status', 'Saved.');
    }

    public function destroy(Request $request, GratitudeEntry $entry): RedirectResponse
    {
        $this->mine($request, $entry);

        $entry->delete();

        return redirect()->route('gratitude.index')->with('status', 'Removed.');
    }

    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function mine(Request $request, GratitudeEntry $entry): void
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
    }

    private function ownedDesireId(Request $request, $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $request->user()->desires()->whereKey($id)->value('id');
    }

    /** @return Collection<string, Collection<int, GratitudeEntry>> */
    private function groupByDay(Collection $entries): Collection
    {
        return $entries
            ->sortByDesc('for_date')
            ->groupBy(fn (GratitudeEntry $e) => $e->for_date->toDateString());
    }

    private function tagsInUse($user): Collection
    {
        // tag_index is '|a|b|c|'. Splitting it beats decrypting every entry
        // just to build a filter bar.
        return $user->gratitudeEntries()
            ->whereNotNull('tag_index')
            ->pluck('tag_index')
            ->flatMap(fn ($index) => array_filter(explode('|', $index)))
            ->countBy()
            ->sortDesc()
            ->take(12);
    }

    /**
     * Consecutive days written, counting back from today.
     *
     * Today not being written yet does not break a streak — it has not been
     * missed until the day is over. Anything else would punish someone for
     * opening the app in the morning.
     */
    private function streak($user): int
    {
        $days = $user->gratitudeEntries()
            ->reorder()
            ->distinct()
            ->pluck('for_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = today();

        if (! $days->has($cursor->toDateString())) {
            $cursor = $cursor->copy()->subDay();
        }

        $streak = 0;

        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->copy()->subDay();
        }

        return $streak;
    }

    /** The current week, Sunday first, for the row of day markers. */
    private function week($user): array
    {
        $start = today()->startOfWeek(Carbon::SUNDAY);

        $written = $user->gratitudeEntries()
            ->reorder()
            ->whereDate('for_date', '>=', $start)
            ->pluck('for_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        return collect(range(0, 6))->map(function (int $i) use ($start, $written) {
            $day = $start->copy()->addDays($i);

            return [
                'letter'  => $day->format('D')[0],
                'label'   => $day->format('l'),
                'written' => $written->has($day->toDateString()),
                'today'   => $day->isToday(),
                'future'  => $day->isFuture(),
            ];
        })->all();
    }
}
