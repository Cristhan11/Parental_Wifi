<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One-off alert when a blocked site is attempted — rendered by {@see resources/views/emails/reports/immediate-blocked.blade.php}.
 *
 * Dispatched from {@see \App\Listeners\SendImmediateBlockedWebsiteAlert} after {@see \App\Events\BlockedWebsiteAccessed} fires.
 */
class ImmediateBlockedWebsiteAlertMail extends Mailable
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
            view: 'emails.reports.immediate-blocked',
            with: [
                'payload' => $this->payload,
            ]
        );
    }
}

