<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Confirming an email address.
 *
 * Why this exists at all: generation costs real money on someone else's API,
 * and before this anyone who found the URL could sign up with an address they
 * did not own and start spending it. An invite code closes that during the
 * beta; verification is what keeps it closed afterwards, when the codes stop.
 *
 * It is deliberately not a wall in front of the app. The 'verified' middleware
 * sits on the four routes that call a provider, so someone waiting on an email
 * can still sign in, read the privacy disclosure, fill in My World and name a
 * desire — they simply cannot spend anything yet.
 *
 * Mail must work for this, but that is not a new requirement: password reset
 * already needs it. An install with MAIL_MAILER=log has a broken reset flow
 * whether or not this file exists.
 */
class EmailVerificationController extends Controller
{
    /** "Check your email" — where the middleware sends an unverified user. */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('today'))
            : view('auth.verify-email');
    }

    /**
     * The link in the email.
     *
     * EmailVerificationRequest does the work and is worth using rather than
     * hand-rolling: it validates the signature, checks the {id} against the
     * signed-in user and the {hash} against a hash of their *current* email —
     * so a link stops working the moment the address it was sent to changes.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        // Already done — a second click on the same link, which people do.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('today')->with('status', 'Your email is already confirmed.');
        }

        $request->fulfill();

        return redirect()->route('today')
            ->with('status', 'Confirmed. Everything is open to you now.');
    }

    /** Send it again. Throttled at the route, because it sends mail. */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('today');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Sent. Check your spam folder too — it often lands there.');
    }
}
