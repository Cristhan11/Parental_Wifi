<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel “Mailable” = one email message definition (subject + Blade view + data).
 *
 * This class is used for DAILY digests only. Weekly/Monthly classes are almost identical but point at
 * different Blade entry files — that makes it easy to customize one cadence later without touching others.
 *
 * How it is constructed:
 * - `$payload` = big associative array from {@see \App\Services\ReportingDigestService::buildDigestPayload}.
 * - `$subjectLine` = already formatted string from {@see \App\Jobs\DispatchDigestReportJob::buildSubject}.
 *
 * Methods:
 * - `envelope()` — metadata (subject, from, reply-to). We only set subject here.
 * - `content()` — which Blade view to render and what variables to pass (`with`).
 */
class DailyDigestReportMail extends Mailable
{
    /**
     * Queueable: allows this mailable to be queued (we currently `send()` synchronously inside the job).
     * SerializesModels: if you passed Eloquent models, Laravel would serialize ids — we pass arrays only.
     */
    use Queueable, SerializesModels;

    /**
     * Constructor property promotion: creates `$this->payload` and `$this->subjectLine` as public readonly properties.
     * Blade can access them as `$payload` when passed via `with` below (key name must match).
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $subjectLine
    ) {
    }

    /**
     * Named arguments (`subject:`) require PHP 8+; they map to Envelope constructor parameters.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine
        );
    }

    /**
     * `view:` is the dot path under resources/views (no .blade.php).
     * `with` passes variables into the Blade template — `daily-digest.blade.php` receives `$payload`.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.daily-digest',
            with: [
                'payload' => $this->payload,
            ]
        );
    }
}
