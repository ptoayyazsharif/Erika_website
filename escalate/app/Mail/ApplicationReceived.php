<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent the moment somebody applies, so they know it arrived. */
class ApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Escalate application');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.application-received');
    }
}
