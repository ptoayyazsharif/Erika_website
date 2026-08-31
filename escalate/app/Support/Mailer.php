<?php

namespace App\Support;

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
}
