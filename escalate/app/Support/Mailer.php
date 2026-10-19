<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Send, but never at the cost of the thing that just happened.
 *
 * Every send in this application follows something already saved: an
 * application recorded, an invite minted. If Resend is down, or the key was
 * rotated, or the address bounces at the transport, the right outcome is a
 * missing email and a logged fault — not a 500 that discards the row and asks
 * a stranger to fill the form in again.
 *
 * The exception to the rule is Admin\SettingsController's test button, which
 * exists precisely to surface a mail failure and so must not use this.
 */
class Mailer
{
    /** @return bool whether it was handed to the mail server */
    public static function send(string $to, Mailable $mailable): bool
    {
        // Nothing is delivered while the mailer is `log`, and pretending
        // otherwise is how password reset came to be silently broken.
        if (config('mail.default') === 'log') {
            logger()->warning('Mail not sent: mailer is set to log.', [
                'to'       => $to,
                'mailable' => $mailable::class,
            ]);

            return false;
        }

        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Send one message to every administrator who can act on it.
     *
     * Separately rather than as one message with several recipients, so that
     * one bad address cannot take the others down with it — each goes through
     * send() above and so fails on its own.
     *
     * Inline, like every other send in this app. The queue exists and a worker
     * runs, but putting this on it would buy a second failure mode — a job
     * dying in the worker where nobody is looking — to save about a second on a
     * form that will be submitted a few dozen times. If the admin list ever
     * grows past a handful, this is the thing to move.
     *
     * @return int how many were handed to the mail server
     */
    public static function toAdmins(Mailable $mailable): int
    {
        return User::admins()->get()
            ->filter(fn (User $admin) => self::send($admin->email, clone $mailable))
            ->count();
    }
}
