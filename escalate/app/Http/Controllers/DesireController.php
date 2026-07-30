<?php

namespace App\Http\Controllers;

use App\Models\Desire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Desires, and the Manifestation Archive they live in.
 *
 * Ownership is enforced by only ever reaching a desire through
 * $request->user()->desires(), never through Desire::find(). That is why there
 * is no policy call in this controller: a row that belongs to someone else is
 * not found rather than forbidden, which is both safer and less informative to
 * a prober.
 */
class DesireController extends Controller
{
    public const CATEGORIES = [
        'work'         => 'Work & calling',
        'money'        => 'Money & freedom',
        'home'         => 'Home & place',
        'love'         => 'Love & partnership',
        'family'       => 'Family',
        'health'       => 'Health & body',
        'growth'       => 'Growth & character',
        'creative'     => 'Creative work',
        'community'    => 'Community & service',
        'other'        => 'Something else',
    ];

    public const TIMEFRAMES = [
        'this_month' => 'Within a month',
        'this_year'  => 'Within the year',
        'three_year' => 'About three years out',
        'someday'    => 'Someday — no date',
    ];

    public const FEELINGS = [
        'calm', 'safe', 'proud', 'free', 'held', 'capable', 'awake',
        'generous', 'steady', 'light', 'certain', 'useful',
    ];

    public function index(Request $request): View
    {
        $filter = (string) $request->query('status', '');
        $query = $request->user()->desires();

        if (array_key_exists($filter, config('escalate.statuses'))) {
            $query->where('status', $filter);
        }

        $desires = $query->withCount('stories')->get();

        return view('desires.index', [
            'desires' => $desires,
            'filter'  => $filter,
            'counts'  => $request->user()->desires()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('desires.create', [
            'profile' => $request->user()->world(),
            'circle'  => $request->user()->circle,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // status and status_changed_at are not mass-assignable — a client must
        // never be able to declare a desire "Manifested" on creation — so they
        // are set with forceFill after the validated fields are filled. Passing
        // them to create() looks right and silently does nothing: status would
        // survive only on the column default, and status_changed_at would stay
        // null forever.
        $desire = $request->user()->desires()->make($data);
        $desire->forceFill([
            'status' => 'desired',
            'status_changed_at' => now(),
        ])->save();

        return redirect()
            ->route('desires.show', $desire)
            ->with('status', 'Named. Now it can be written.');
    }

    public function show(Request $request, Desire $desire): View
    {
        $this->mine($request, $desire);

        return view('desires.show', [
            'desire'  => $desire->load('stories', 'rewind'),
            'stories' => $desire->stories,
        ]);
    }

    public function edit(Request $request, Desire $desire): View
    {
        $this->mine($request, $desire);

        return view('desires.create', [
            'desire'  => $desire,
            'profile' => $request->user()->world(),
            'circle'  => $request->user()->circle,
        ]);
    }

    public function update(Request $request, Desire $desire): RedirectResponse
    {
        $this->mine($request, $desire);

        $desire->update($this->validated($request));

        return redirect()->route('desires.show', $desire)->with('status', 'Saved.');
    }

    /** Move a desire through the archive. */
    public function status(Request $request, Desire $desire): RedirectResponse
    {
        $this->mine($request, $desire);

        $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.statuses')))],
        ]);

        $desire->moveTo($request->string('status'));

        // Reaching a terminal status is what unlocks a Rewind, so say so rather
        // than letting the user discover the feature by accident.
        $message = $desire->isComplete() && ! $desire->rewind
            ? 'Marked as '.$desire->statusLabel().'. You can write a Rewind for it now.'
            : 'Moved to '.$desire->statusLabel().'.';

        return redirect()->route('desires.show', $desire)->with('status', $message);
    }

    public function destroy(Request $request, Desire $desire): RedirectResponse
    {
        $this->mine($request, $desire);

        $desire->delete();

        return redirect()->route('desires.index')->with('status', 'Deleted, along with everything written for it.');
    }

    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function mine(Request $request, Desire $desire): void
    {
        abort_unless($desire->user_id === $request->user()->id, 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:120'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'why_it_matters'   => ['nullable', 'string', 'max:1200'],
            'non_negotiables'  => ['nullable', 'string', 'max:600'],
            'desired_feelings' => ['nullable', 'array', 'max:5'],
            'desired_feelings.*' => ['string', 'max:30'],
            'people_involved'  => ['nullable', 'array', 'max:12'],
            'people_involved.*' => ['string', 'max:60'],

            'category'      => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'timeframe'     => ['required', 'string', 'in:'.implode(',', array_keys(self::TIMEFRAMES))],
            'open_to_better' => ['nullable', 'boolean'],
            'story_length'  => ['required', 'string', 'in:'.implode(',', array_keys(config('escalate.lengths')))],
            'perspective'   => ['required', 'string', 'in:first,second'],
            'tone'          => ['required', 'string', 'in:grounded,tender,assured,reverent,playful'],
        ]);

        $data['open_to_better'] = $request->boolean('open_to_better');
        $data['desired_feelings'] = collect($data['desired_feelings'] ?? [])->filter()->values()->all();
        $data['people_involved'] = collect($data['people_involved'] ?? [])->filter()->values()->all();

        return $data;
    }
}
