<?php

namespace App\Http\Controllers;

use App\Jobs\WriteAffirmations;
use App\Models\Affirmation;
use App\Models\AffirmationSet;
use App\Support\Ceiling;
use App\Support\Quota;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daily affirmation cards.
 *
 * One set per person per day, enforced by a unique index on
 * (user_id, for_date) rather than by checking first — two taps on a slow
 * connection race, and the database is the only thing that can settle it.
 */
class AffirmationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $today = $user->affirmationSets()
            ->with('affirmations.desire')
            ->where('for_date', today())
            ->first();

        return view('affirmations.index', [
            'set'    => $today,
            'recent' => $user->affirmationSets()
                ->with('affirmations')
                ->where('for_date', '<', today())
                ->orderByDesc('for_date')
                ->take(7)
                ->get(),
            'favourites' => $user->affirmations()
                ->where('favourite', true)
                ->latest()
                ->take(12)
                ->get(),
            'remaining' => Quota::remaining($user, 'affirmation'),
        ]);
    }

    /** Draw today's cards. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! Quota::allows($user, 'affirmation')) {
            return back()->with('status', Quota::message($user, 'affirmation'));
        }

        if (! Ceiling::allows('affirmation')) {
            return back()->with('status', Ceiling::message());
        }

        $set = $user->affirmationSets()->firstWhere('for_date', today());
        $fresh = false;

        /*
         * The unique (user_id, for_date) index settles the race, not a check.
         *
         * Two taps a second apart on a phone both pass a look-then-insert, and
         * the second either duplicates the day or dies on the index. Inserting
         * and catching the violation means the loser of the race is handed the
         * winner's row and no set is ever paid for twice.
         *
         * Not firstOrCreate(): user_id and state are deliberately absent from
         * $fillable — state especially, so a client cannot post state=ready —
         * and mass assignment drops them both, which writes a row with no owner.
         */
        if (! $set) {
            try {
                $set = new AffirmationSet;
                $set->forceFill([
                    'user_id'  => $user->id,
                    'for_date' => today(),
                    'state'    => 'queued',
                ])->save();

                $fresh = true;
            } catch (QueryException $e) {
                $set = $user->affirmationSets()->firstWhere('for_date', today());

                // Not the unique index, then — something else is wrong.
                if (! $set) {
                    throw $e;
                }
            }
        }

        // A failed set may be tried again; a ready or in-flight one is left
        // alone rather than paid for a second time.
        if ($fresh || $set->state === 'failed') {
            $set->forceFill(['state' => 'queued', 'failure_reason' => null])->save();

            WriteAffirmations::dispatch($set);
        }

        return redirect()->route('affirmations');
    }

    /** Keep a card. This is the only thing a person may change about one. */
    public function favourite(Request $request, Affirmation $affirmation): RedirectResponse
    {
        abort_unless($affirmation->user_id === $request->user()->id, 404);

        $affirmation->forceFill(['favourite' => ! $affirmation->favourite])->save();

        return back();
    }

    /**
     * What the drawing screen polls.
     *
     * Only what the screen needs to decide what to draw — no model name, no
     * provider, no prompt.
     */
    public function state(Request $request): JsonResponse
    {
        $set = $request->user()->affirmationSets()
            ->with('affirmations')
            ->where('for_date', today())
            ->first();

        return response()->json([
            'state' => $set?->state ?? 'none',
            'cards' => $set?->isReady()
                ? $set->affirmations->map(fn ($a) => [
                    'id'   => $a->id,
                    'body' => $a->body,
                    'back' => $a->back,
                ])->all()
                : [],
            'reason' => $set?->state === 'failed' ? $set->failure_reason : null,
        ]);
    }
}
