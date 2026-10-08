<?php

namespace App\Domains\Notifications\Support;

use App\Domains\Notifications\DTOs\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Enveloppe Laravel d'un `MailMessage`, pour le pilote `LaravelMailSender`.
 */
final class TransactionalMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MailMessage $mailMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailMessage->subject());
    }

    public function content(): Content
    {
        return new Content(
            view: $this->mailMessage->template->view(),
            with: $this->mailMessage->data,
        );
    }
}
