<?php

namespace App\Domains\Notifications\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Enveloppe Laravel d'un contenu libre, pour `LaravelMailSender::sendRaw()`. */
final class RawMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $mailSubject, private readonly string $bodyHtml) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->bodyHtml);
    }
}
