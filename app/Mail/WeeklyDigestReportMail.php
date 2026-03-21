<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Weekly digest Mailable — structurally identical to {@see DailyDigestReportMail} but uses view `emails.reports.weekly-digest`.
 * The *data* inside `$payload` still comes from the same service; only the reporting window in the job differs.
 */
class WeeklyDigestReportMail extends Mailable
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
            view: 'emails.reports.weekly-digest',
            with: [
                'payload' => $this->payload,
            ]
        );
    }
}
