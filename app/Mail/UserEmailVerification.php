<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserEmailVerification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $token,
    )
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'User Email Verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-verification-mail',
            with: [
                "url" => $this->url,
                "token" => $this->token,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
