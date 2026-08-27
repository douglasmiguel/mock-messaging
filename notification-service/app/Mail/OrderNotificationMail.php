<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $subjectLine,
        public string $title,
        public string $messageText,
        public string $buttonLabel,
        public string $actionUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-notification');
    }
}
