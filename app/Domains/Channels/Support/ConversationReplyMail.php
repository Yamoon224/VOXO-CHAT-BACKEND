<?php

namespace App\Domains\Channels\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Réponse d'un agent relayée par e-mail (canal `email`, section 2.5) : texte
 * libre, pas un modèle fixe — contrairement aux e-mails transactionnels de
 * `Notifications`, cette enveloppe ne passe donc pas par `MailTemplate`.
 */
final class ConversationReplyMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $subjectLine,
        private readonly string $bodyText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(from: new Address($this->fromEmail, $this->fromName), subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: nl2br(e($this->bodyText)));
    }
}
