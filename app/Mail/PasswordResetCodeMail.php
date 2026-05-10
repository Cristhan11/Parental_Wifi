<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Parental WiFi] Your password reset confirmation code'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password-reset-code',
            with: [
                'userName' => $this->user->name,
                'code' => $this->code,
                'expiresMinutes' => 60,
            ]
        );
    }
}
