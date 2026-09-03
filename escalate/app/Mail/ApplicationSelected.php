<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\Invite;
use App\Support\EmailTemplates;
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
        return new Envelope(subject: EmailTemplates::subject('selected', $this->tokens()));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.application-selected', with: [
            'body' => EmailTemplates::body('selected', $this->tokens()),
            'url'  => route('register', ['invite' => $this->invite->code]),
        ]);
    }

    /**
     * Display-only. The code, the button and its URL are in the Blade, so an
     * admin who deletes every token from the prose still sends a usable invite.
     *
     * @return array<string, string>
     */
    private function tokens(): array
    {
        return [
            'name'    => $this->application->name,
            'expires' => $this->invite->expires_at?->format('j F Y') ?? 'no fixed date',
        ];
    }
}
