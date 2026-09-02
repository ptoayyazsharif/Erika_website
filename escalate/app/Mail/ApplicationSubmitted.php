<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to every administrator when somebody applies to the beta.
 *
 * Carries the answers, not just a nudge to go and look. The point is that an
 * application can be read and decided from a phone at a bus stop; a link alone
 * would mean signing in to the admin panel before knowing whether it was worth
 * signing in to the admin panel.
 *
 * That does move the answers outside the app — they are encrypted at rest
 * precisely because question one asks what part of somebody's life they are
 * working on — and once mailed they sit in inboxes and in the provider's logs
 * beyond AccountEraser's reach. It is the same footing the admin screen already
 * stands on (an application is addressed *to* the reader, which is why
 * "counts, never content" does not cover it), but it is a step further out, so
 * `escalate.beta.notify_admins` can switch the whole thing off without a deploy.
 *
 * $isUpdate exists because store() lets somebody re-apply and replaces their
 * answers in place. An administrator who has already read the first version
 * needs to be told the second one is different rather than getting a second
 * mail that claims to be new.
 */
class ApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public bool $isUpdate = false,
    ) {}

    public function envelope(): Envelope
    {
        $what = $this->isUpdate ? 'Updated application' : 'New application';

        // The applicant's name is in the subject so a full inbox stays
        // scannable, and so two applications never look like the same mail.
        return new Envelope(subject: "{$what}: {$this->application->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.application-submitted');
    }
}
