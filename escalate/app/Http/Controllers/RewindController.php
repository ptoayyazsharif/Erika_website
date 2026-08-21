<?php

namespace App\Http\Controllers;

use App\Jobs\WriteRewind;
use App\Models\Desire;
use App\Models\Rewind;
use App\Support\Ceiling;
use App\Support\Quota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rewinds — looking back at a desire once it has finished moving.
 *
 * This was a placeholder, and the desire screen has been offering a "Begin a
 * Rewind" button that led straight to it: someone finishes the thing the whole
 * app is about, presses the one button offered to mark it, and lands on "not
 * written yet".
 *
 * The shape is deliberately the reverse of a reading. A reading is imagined
 * and the app writes it. A Rewind is remembered and the *person* writes it —
 * six questions, all optional — and only then does the app assemble their own
 * answers into something continuous. Their words are the source; the generated
 * narrative is the derivative, and it can be thrown away and rebuilt without
 * losing anything they typed.
 */
class RewindController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('rewinds.index', [
            'rewinds' => $user->rewinds()->with('desire')->get(),
            // Finished desires with nothing written about them yet: the whole
            // reason to be on this screen.
            'waiting' => $user->desires()->complete()->doesntHave('rewind')->get(),
        ]);
    }

    /** The reflection form — for a desire that has finished, or an existing draft. */
    public function create(Request $request, Desire $desire): View
    {
        $this->mine($request, $desire);

        // A Rewind is a looking-back, so there has to be something to look back
        // at. Offering one for a desire still in motion would be asking someone
        // to write the ending of a story they are still inside.
        abort_unless($desire->isComplete(), 404);

        return view('rewinds.edit', [
            'desire' => $desire,
            'rewind' => $desire->rewind,
            'remaining' => Quota::remaining($request->user(), 'rewind'),
        ]);
    }

    public function store(Request $request, Desire $desire): RedirectResponse
    {
        $this->mine($request, $desire);
        abort_unless($desire->isComplete(), 404);

        $data = $request->validate([
            'what_happened'  => ['nullable', 'string', 'max:4000'],
            'turning_points' => ['nullable', 'string', 'max:4000'],
            'redirections'   => ['nullable', 'string', 'max:4000'],
            'people'         => ['nullable', 'string', 'max:2000'],
            'lessons'        => ['nullable', 'string', 'max:4000'],
            'gratitude'      => ['nullable', 'string', 'max:2000'],
        ]);

        $rewind = $desire->rewind ?: new Rewind;
        $rewind->fill($data);
        // user_id and desire_id are server-derived; they are not in $fillable
        // precisely so a posted field can never reassign a Rewind to someone
        // else's desire.
        $rewind->forceFill([
            'user_id'   => $request->user()->id,
            'desire_id' => $desire->id,
            'state'     => $rewind->state ?: 'draft',
        ])->save();

        return redirect()
            ->route('rewinds.show', $rewind)
            ->with('status', 'Saved. Nothing here is final — you can keep adding to it.');
    }

    public function show(Request $request, Rewind $rewind): View
    {
        $this->mineRewind($request, $rewind);

        return view('rewinds.show', [
            'rewind'    => $rewind->load('desire'),
            'remaining' => Quota::remaining($request->user(), 'rewind'),
        ]);
    }

    /** Hand the answers to the writer. */
    public function generate(Request $request, Rewind $rewind): RedirectResponse
    {
        $this->mineRewind($request, $rewind);

        // Nothing written means nothing to work from, and the prompt forbids
        // inventing. Asking anyway would spend a generation to produce a page
        // of hedging.
        if ($rewind->answered() === []) {
            return back()->with('status', 'Answer at least one question first — the Rewind is written from your words, not instead of them.');
        }

        if ($rewind->isPending()) {
            return back();
        }

        if (! Quota::allows($request->user(), 'rewind')) {
            return back()->with('status', Quota::message('rewind'));
        }

        if (! Ceiling::allows('rewind')) {
            return back()->with('status', Ceiling::message());
        }

        $rewind->forceFill(['state' => 'queued', 'failure_reason' => null])->save();

        WriteRewind::dispatch($rewind);

        return back();
    }

    public function destroy(Request $request, Rewind $rewind): RedirectResponse
    {
        $this->mineRewind($request, $rewind);

        $rewind->delete();

        return redirect()->route('rewinds.index')->with('status', 'Rewind deleted.');
    }

    /*
    | Ownership is re-checked here rather than relying on scoped route binding,
    | for the reason stated at the top of routes/web.php: a 404 rather than a
    | 403, because 403 confirms the row exists.
    */
    private function mine(Request $request, Desire $desire): void
    {
        abort_unless($desire->user_id === $request->user()->id, 404);
    }

    private function mineRewind(Request $request, Rewind $rewind): void
    {
        abort_unless($rewind->user_id === $request->user()->id, 404);
    }
}
