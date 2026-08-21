<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Plan;
use App\Support\Quota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * People, and what they are allowed.
 *
 * What an administrator can see here is bounded on purpose: name, email, when
 * they joined and last signed in, how much they have generated, their plan.
 * Nothing they wrote. Every desire, reading, gratitude entry and rewind stays
 * encrypted and unread — the counts come from ai_events and row counts, which
 * hold no content.
 *
 * That is not squeamishness. The product is a private journal; an admin screen
 * that renders it makes "we cannot read your entries" false, and the privacy
 * disclosure at /privacy says otherwise.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = scalar_input($request->query('q'));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q
                ->where('email', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->withCount(['desires', 'stories'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function show(Request $request, User $user): View
    {
        return view('admin.users.show', [
            'person'    => $user,
            'plan'      => $user->planKey(),
            'plans'     => Plan::all(),
            'usage'     => collect(['story', 'narration', 'rewind'])->mapWithKeys(fn ($kind) => [
                $kind => [
                    'used'  => Quota::used($user, $kind),
                    'limit' => Quota::limit($user, $kind),
                ],
            ])->all(),
            'counts'    => [
                'desires'   => $user->desires()->count(),
                'stories'   => $user->stories()->count(),
                'gratitude' => $user->gratitudeEntries()->count(),
                'rewinds'   => $user->rewinds()->count(),
            ],
        ]);
    }

    /**
     * Put someone on a plan by hand, or take the override away.
     *
     * Does not touch Stripe. Comping somebody must not write rows that look
     * like a payment nobody made — Plan::for() checks the override first and
     * says so. Removing it returns them to whatever their subscription says,
     * which for most people is the free plan.
     */
    public function plan(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['nullable', 'string', 'in:'.implode(',', array_keys(Plan::all()))],
        ]);

        $user->forceFill(['plan_override' => $data['plan'] ?: null])->save();

        return back()->with('status', $data['plan']
            ? "Put on the {$data['plan']} plan by hand. Stripe is untouched."
            : 'Override removed. They are back on whatever they are subscribed to.');
    }

    /**
     * Suspend or restore an account.
     *
     * RejectSuspended enforces this on every request, not just at login, so a
     * suspension takes effect on the suspended person's very next click rather
     * than whenever their session happens to lapse.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        // An administrator locking themselves out cannot undo it from here,
        // and the only way back is a shell on the server.
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot suspend your own account from here.']);
        }

        $suspending = $user->suspended_at === null;

        $user->forceFill(['suspended_at' => $suspending ? now() : null])->save();

        return back()->with('status', $suspending
            ? 'Suspended. They are signed out on their next request.'
            : 'Restored.');
    }
}
