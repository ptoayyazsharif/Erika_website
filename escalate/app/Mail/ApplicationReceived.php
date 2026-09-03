<?php

namespace App\Mail;

use App\Models\Application;
use App\Support\EmailTemplates;
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
        return new Envelope(subject: EmailTemplates::subject('applied', $this->tokens()));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.application-received', with: [
            'body' => EmailTemplates::body('applied', $this->tokens()),
        ]);
    }

    /** @return array<string, string> */
    private function tokens(): array
    {
        return ['name' => $this->application->name];
    }
}
