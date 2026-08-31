<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "You're in" — and the code, and the one link that uses it.
 *
 * The invite code is in the body as well as the link because people read mail
 * on one device and sign up on another, and a code you can copy is worth more
 * than a link you cannot follow.
 */
class ApplicationSelected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application, public Invite $invite) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You’re in — welcome to the Escalate private beta');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.application-selected', with: [
            'url' => route('register', ['invite' => $this->invite->code]),
        ]);
    }
}
