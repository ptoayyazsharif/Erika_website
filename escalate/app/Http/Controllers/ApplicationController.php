<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationReceived;
use App\Mail\ApplicationSubmitted;
use App\Models\Application;
use App\Support\Mailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The private beta application form.
 *
 * Unauthenticated, public, writes to the database and sends mail — the same
 * class of surface as /register and /forgot-password, and it gets the same
 * treatment those had to be given:
 *
 *   - throttled at the route, because it can be used to send mail
 *   - scalar_input() on everything, because `?name[]=x` on an untyped read is
 *     how five pages here were made to return a 500
 *   - a honeypot, because this is the one form on the site with no invite gate
 *     in front of it
 *   - the same answer whether or not the address has applied before
 *
 * That last one matters as much here as it does on the register form. "You have
 * already applied" tells anyone who asks whether a given person is trying to
 * get into a manifestation app, which is precisely the inference the rest of
 * this application refuses to leak.
 */
class ApplicationController extends Controller
{
    /** What everybody is told, whether or not it is the first time. */
    private const RECEIVED = 'Thank you. Your application is in — we read every one, and you will hear from us by email.';

    public function create(): View
    {
        return view('apply');
    }

    public function store(Request $request): RedirectResponse
    {
        // Bots fill in every field they find. A human never sees this one.
        if (filled(scalar_input($request->input('website')))) {
            return redirect()->route('apply')->with('status', self::RECEIVED);
        }

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],

            'changing'      => ['required', 'string', 'max:2000'],
            'practice'      => ['required', 'string', 'max:2000'],
            'tried_apps'    => ['required', 'string', 'max:2000'],
            'will_use'      => ['required', 'string', 'max:2000'],
            'will_feedback' => ['required', 'string', 'max:2000'],

            'agree' => ['accepted'],
        ], [
            'agree.accepted' => 'Please confirm you have read what happens to what you write.',
            // Quotes nothing from the question itself, so rewording it in the
            // admin panel cannot leave this sentence contradicting it.
            'changing.required' => 'This one matters most — please answer the first question.',
        ]);

        $email = Application::normaliseEmail($data['email']);

        /*
         * A second application from the same address updates the first.
         *
         * Not `unique:applications,email` in the rules above: that would answer
         * "this email has already applied" to anybody who asked. Silently
         * replacing the answers gives the same response either way, and is also
         * the kinder behaviour — somebody who thinks of a better answer and
         * applies again should not be told off.
         *
         * A decision already made is left alone. Re-applying must not reset
         * somebody from selected back to pending.
         */
        $application = Application::where('email', $email)->first() ?? new Application;

        if ($application->exists && ! $application->isPending()) {
            return redirect()->route('apply')->with('status', self::RECEIVED);
        }

        // Captured before the save, which makes exists() true either way. The
        // early return above means this can only be an update of a still-
        // pending application — a decided one never reaches here.
        $isUpdate = $application->exists;

        $application->forceFill([
            'name'          => scalar_input($data['name']),
            'email'         => $email,
            'changing'      => scalar_input($data['changing']),
            'practice'      => scalar_input($data['practice']),
            'tried_apps'    => scalar_input($data['tried_apps']),
            'will_use'      => scalar_input($data['will_use']),
            'will_feedback' => scalar_input($data['will_feedback']),
            'status'        => Application::PENDING,
        ])->save();

        // Both sends happen after the row is safe, and both go through Mailer,
        // which swallows a transport fault. A mail failure must not lose an
        // application — better a silent thank-you than a discarded answer.
        Mailer::send($email, new ApplicationReceived($application));

        // The people who have to read it. Without this the only way to find a
        // new application is to remember to open the admin panel, which is how
        // somebody ends up waiting three days for a reply.
        if (config('escalate.beta.notify_admins')) {
            Mailer::toAdmins(new ApplicationSubmitted($application, $isUpdate));
        }

        return redirect()->route('apply')->with('status', self::RECEIVED);
    }
}
