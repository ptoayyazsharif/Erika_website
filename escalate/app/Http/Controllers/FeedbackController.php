<?php

namespace App\Http\Controllers;

use App\Models\FeedbackResponse;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The day-seven survey.
 *
 * Offered, never imposed. The nudge appears on Today once somebody has had the
 * app a week and disappears the moment they answer or say not now; there is no
 * version of this that stands between a person and their own journal.
 */
class FeedbackController extends Controller
{
    /** How long somebody has to have had it before being asked. */
    public const AFTER_DAYS = 7;

    /** Set when somebody says "not now", for that session only. */
    public const DEFERRED = 'feedback.deferred';

    public function create(Request $request): View
    {
        return view('feedback', [
            'existing' => $request->user()->feedbackResponse,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'disappointment' => ['required', 'string', 'in:'.implode(',', array_keys(FeedbackResponse::FEELINGS))],
            'who_for'        => ['nullable', 'string', 'max:2000'],
            'benefit'        => ['nullable', 'string', 'max:2000'],
            'improve'        => ['nullable', 'string', 'max:2000'],
        ], [
            'disappointment.required' => 'Please pick one — it is the only answer we really need.',
        ]);

        $user = $request->user();

        /*
         * Insert and catch, rather than check and insert.
         *
         * user_id is unique, so a double submission would otherwise either
         * duplicate or die. Somebody who answers twice is amending, not
         * duplicating, so the second one updates the first.
         */
        try {
            $response = $user->feedbackResponse ?? new FeedbackResponse;

            $response->forceFill([
                'user_id'        => $user->id,
                'disappointment' => $data['disappointment'],
                'who_for'        => scalar_input($data['who_for'] ?? null) ?: null,
                'benefit'        => scalar_input($data['benefit'] ?? null) ?: null,
                'improve'        => scalar_input($data['improve'] ?? null) ?: null,
            ])->save();
        } catch (QueryException $e) {
            // Lost a race with themselves; the row that won is the answer.
            if (! $user->fresh()->feedbackResponse) {
                throw $e;
            }
        }

        return redirect()->route('today')->with('status',
            'Thank you — that is genuinely useful, and it is read.');
    }

    /** "Not now." Hidden for this session; offered again next time. */
    public function defer(Request $request): RedirectResponse
    {
        $request->session()->put(self::DEFERRED, true);

        return back();
    }

    /**
     * Whether to put the nudge on Today.
     *
     * Static so the Today screen can ask without this controller being
     * involved in rendering it.
     */
    public static function isDue(User $user, Request $request): bool
    {
        if ($request->session()->get(self::DEFERRED)) {
            return false;
        }

        if ($user->feedbackResponse) {
            return false;
        }

        return $user->created_at?->lte(now()->subDays(self::AFTER_DAYS)) ?? false;
    }
}
