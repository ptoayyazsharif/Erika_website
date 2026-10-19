<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationSelected;
use App\Models\Application;
use App\Models\Invite;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reading the applications, and letting people in.
 *
 * Selecting somebody does four things that belong together, so they happen in
 * one transaction: the application is marked, an invite is minted bound to
 * their address, the cohort is recorded on the invite's note, and the code is
 * emailed. The mail is sent AFTER the transaction commits — a mail failure
 * must not roll back a decision, and a code that exists unsent can be resent,
 * whereas a decision lost to a network fault is just gone.
 */
class ApplicationController extends Controller
{
    /** The note that marks a Founding 25 invite, and later the cohort. */
    public const COHORT = 'Founding 25';

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), [
            Application::PENDING, Application::SELECTED, Application::WAITLISTED,
        ], true) ? $request->query('status') : null;

        return view('admin.applications.index', [
            'applications' => Application::with('invite')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(50)
                ->withQueryString(),
            'status' => $status,
            'counts' => [
                'pending'    => Application::where('status', Application::PENDING)->count(),
                'selected'   => Application::where('status', Application::SELECTED)->count(),
                'waitlisted' => Application::where('status', Application::WAITLISTED)->count(),
            ],
            // The Founding 25 is a number, and the point of it is that it runs
            // out. Say how many seats are gone on the screen where they go.
            'seats' => (int) config('escalate.beta.founding_seats'),
        ]);
    }

    public function show(Application $application): View
    {
        return view('admin.applications.show', ['application' => $application]);
    }

    public function select(Request $request, Application $application): RedirectResponse
    {
        if (! $application->isPending()) {
            return back()->withErrors(['application' => 'That application has already been decided.']);
        }

        $invite = DB::transaction(function () use ($application, $request) {
            // Bound to their address: a Founding 25 seat is for the person who
            // applied, not for whoever they forward the email to.
            $invite = Invite::mint(
                $application->email,
                self::COHORT,
                (int) config('escalate.beta.invite_days') ?: null,
            );

            $application->forceFill([
                'status'     => Application::SELECTED,
                'decided_at' => now(),
                'decided_by' => $request->user()->id,
                'invite_id'  => $invite->id,
            ])->save();

            return $invite;
        });

        $sent = Mailer::send($application->email, new ApplicationSelected($application, $invite));

        return back()->with('status', $sent
            ? "Selected. The code is on its way to {$application->email}."
            : "Selected, and the invite is minted — but the email did not send. The code is {$invite->code}; check Settings → Mail.");
    }

    /**
     * Not selected, which is the waitlist rather than a refusal.
     *
     * No email goes out here. Erika's funnel puts these people in front of the
     * public launch, and "you did not get in" is a worse message than silence
     * followed later by "we are open".
     */
    public function decline(Request $request, Application $application): RedirectResponse
    {
        if (! $application->isPending()) {
            return back()->withErrors(['application' => 'That application has already been decided.']);
        }

        $application->forceFill([
            'status'     => Application::WAITLISTED,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'Moved to the waitlist. Nothing has been emailed to them.');
    }
}
