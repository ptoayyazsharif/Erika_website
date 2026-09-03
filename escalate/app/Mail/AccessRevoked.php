<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a seat somebody never claimed is taken back.
 *
 * Optional — `escalate.beta.notify_revoked` decides whether it goes at all,
 * because there is a real case for saying nothing to somebody who never
 * engaged, and a real case for not leaving them wondering. That is Erika's
 * call, not a decision to bake in.
 *
 * The tone matters more than usual here. This is the only email in the app
 * that tells somebody they lost something, and they did nothing wrong — they
 * were busy. So it reads as the seat going to somebody on the waiting list,
 * which is true, and it says they are still on the list, which is also true:
 * revoking moves the application to waitlisted rather than declining it.
 */
class AccessRevoked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Escalate invite has been released');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.access-revoked');
    }
}
