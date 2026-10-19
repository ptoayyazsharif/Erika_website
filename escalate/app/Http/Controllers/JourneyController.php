<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * My Journey — everything that has happened, in the order it happened.
 *
 * This was a placeholder, which is the worst possible thing for this
 * particular screen: the whole premise of the app is that naming something and
 * watching it move is worth doing, and the screen meant to show that movement
 * showed "not written yet".
 *
 * Nothing here is new data. Every event already existed — a desire named, a
 * status moved, a reading written, a gratitude entry kept — scattered across
 * four screens that each show one kind of thing. The value is entirely in
 * putting them on one line in date order, because that is the only view in
 * which "I have been doing this for four months" is visible at all.
 */
class JourneyController extends Controller
{
    /**
     * Enough to show a real history without loading a lifetime into memory.
     *
     * Each source is capped separately before merging, so one very chatty
     * source — gratitude, usually — cannot crowd the others out of the page.
     */
    private const PER_SOURCE = 120;

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $events = collect()
            ->concat($this->desireEvents($user))
            ->concat($this->readingEvents($user))
            ->concat($this->gratitudeEvents($user))
            ->sortByDesc('at')
            ->values();

        return view('journey', [
            'user'    => $user,
            'months'  => $events->groupBy(fn ($event) => $event['at']->format('F Y')),
            'started' => $user->created_at,
            'stats'   => $this->stats($user),
        ]);
    }

    /**
     * Naming a desire, and later moving it.
     *
     * Only the *current* status is stored — there is no history table — so a
     * desire contributes at most two events: when it was named, and when it
     * last moved. That is an honest limit rather than an invented history:
     * showing a fabricated chain of every status it might have passed through
     * would be worse than showing the one transition we can actually prove.
     */
    private function desireEvents($user): Collection
    {
        $desires = $user->desires()->take(self::PER_SOURCE)->get();
        $events = collect();

        foreach ($desires as $desire) {
            $events->push([
                'at'    => $desire->created_at,
                'icon'  => 'compass',
                'kind'  => 'named',
                'title' => $desire->title,
                'note'  => 'Named it.',
                'url'   => route('desires.show', $desire),
            ]);

            // Only when it actually moved. A desire still sitting at 'desired'
            // has a status_changed_at from creation, and reporting that as a
            // second event would double every row for no information.
            if ($desire->status !== 'desired' && $desire->status_changed_at) {
                $events->push([
                    'at'    => $desire->status_changed_at,
                    'icon'  => $desire->isComplete() ? 'check' : 'rewind',
                    'kind'  => $desire->status,
                    'title' => $desire->title,
                    'note'  => $desire->statusLabel().' — '.(config("escalate.statuses.{$desire->status}.note") ?? ''),
                    'url'   => route('desires.show', $desire),
                ]);
            }
        }

        return $events;
    }

    private function readingEvents($user): Collection
    {
        return $user->stories()
            ->with('desire')
            ->where('state', 'ready')
            ->take(self::PER_SOURCE)
            ->get()
            ->map(fn ($story) => [
                'at'    => $story->created_at,
                'icon'  => 'book',
                'kind'  => 'reading',
                'title' => $story->desire?->title ?? 'A reading',
                'note'  => $story->word_count
                    ? $story->word_count.' words'.($story->play_count ? ', heard '.$story->play_count.'×' : '')
                    : 'A reading',
                'url'   => route('stories.show', $story),
            ]);
    }

    private function gratitudeEvents($user): Collection
    {
        return $user->gratitudeEntries()
            ->take(self::PER_SOURCE)
            ->get()
            ->map(fn ($entry) => [
                // for_date is the day it is *about*; created_at is when it was
                // typed. The journey is a record of the days themselves.
                'at'    => $entry->for_date ?? $entry->created_at,
                'icon'  => 'heart',
                'kind'  => 'gratitude',
                'title' => \Illuminate\Support\Str::limit(strip_tags($entry->body), 90),
                'note'  => 'Gratitude',
                'url'   => route('gratitude.index'),
            ]);
    }

    private function stats($user): array
    {
        $desires = $user->desires()->count();
        $arrived = $user->desires()->complete()->count();

        return [
            'desires'   => $desires,
            'arrived'   => $arrived,
            'readings'  => $user->stories()->where('state', 'ready')->count(),
            'gratitude' => $user->gratitudeEntries()->count(),
            // Days since the account existed, not since the first entry: the
            // gap between joining and starting is part of the story too.
            'days'      => max(1, $user->created_at->diffInDays(now()) + 1),
        ];
    }
}
