<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Numeric code sent to the *new* address before a profile email change is saved.
 */
class ProfileEmailChangeCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $newEmail,
        public readonly string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Parental WiFi] Confirm your new profile email'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.profile-email-change-code',
            with: [
                'userName' => $this->user->name,
                'newEmail' => $this->newEmail,
                'code' => $this->code,
                'expiresMinutes' => 60,
            ]
        );
    }
}
