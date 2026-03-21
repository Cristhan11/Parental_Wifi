<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One-off alert for flagged-site visits — {@see resources/views/emails/reports/immediate-flagged.blade.php}.
 *
 * Dispatched from {@see \App\Listeners\SendImmediateFlaggedWebsiteAlert} on {@see \App\Events\FlaggedWebsiteVisited}.
 */
class ImmediateFlaggedWebsiteAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly string $subjectLine
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.immediate-flagged',
            with: [
                'payload' => $this->payload,
            ]
        );
    }
}

