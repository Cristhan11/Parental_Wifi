<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a numeric verification code for email verification (same visual language as reporting emails).
 */
class VerifyEmailCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Parental WiFi] Your email verification code'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email-code',
            with: [
                'userName' => $this->user->name,
                'code' => $this->code,
                'expiresMinutes' => 60,
                'verifyUrl' => route('verification.notice', absolute: true),
            ]
        );
    }
}
