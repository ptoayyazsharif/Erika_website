<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * An announcement, in somebody's inbox.
 *
 * The only email in this app that is not a reply to something the person did,
 * which is exactly why it carries an unsubscribe and the others do not.
 *
 * The link is a signed route so it works from a mail client on a device nobody
 * is signed in on — which is where an unsubscribe link is actually clicked —
 * without the id in it being something a stranger can edit to opt somebody else
 * out.
 */
class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->announcement->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.announcement', with: [
            'body' => $this->announcement->html(),
            'unsubscribeUrl' => URL::signedRoute('announcements.unsubscribe', [
                'user' => $this->recipient->id,
            ]),
        ]);
    }
}
