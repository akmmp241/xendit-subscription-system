<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $token,
    )
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Forget Password Mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.forget-password-mail',
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
