<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handing out invites, without a terminal.
 *
 * `escalate:invite` still exists and is still the thing to use when scripting.
 * This is for the ordinary case: somebody asks to try it, and the person
 * answering is on a phone.
 */
class InviteController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.invites', [
            'invites' => Invite::with('claimant')->latest()->paginate(50),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'note'  => ['nullable', 'string', 'max:120'],
            'days'  => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $count = (int) ($data['count'] ?? 1);

        // An address-bound invite is for one person by definition; minting
        // fifty of them would be fifty codes only one of which can ever be used.
        if (filled($data['email'] ?? null)) {
            $count = 1;
        }

        $days = array_key_exists('days', $data) && $data['days'] !== null
            ? (int) $data['days']
            : (int) config('escalate.beta.invite_days');

        foreach (range(1, $count) as $ignored) {
            Invite::mint($data['email'] ?? null, $data['note'] ?? null, $days ?: null);
        }

        return back()->with('status', $count === 1 ? 'One invite minted.' : "{$count} invites minted.");
    }

    /**
     * Withdraw an unclaimed invite.
     *
     * A claimed one is left alone: deleting it would not close the account it
     * created, and it would destroy the only record of how that person got in.
     * Suspend the account instead.
     */
    public function destroy(Request $request, Invite $invite): RedirectResponse
    {
        if ($invite->isClaimed()) {
            return back()->withErrors([
                'invite' => 'That invite has already been used. Deleting it would not close the account it created — suspend the person instead.',
            ]);
        }

        $invite->delete();

        return back()->with('status', 'Invite withdrawn.');
    }
}
