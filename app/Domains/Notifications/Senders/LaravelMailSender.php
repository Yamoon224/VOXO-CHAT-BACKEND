<?php

namespace App\Domains\Notifications\Senders;

use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Notifications\DTOs\MailMessage;
use App\Domains\Notifications\Support\TransactionalMail;
use Illuminate\Contracts\Mail\Mailer;

/**
 * Envoi par le mailer Laravel : le transport réel (SMTP, Resend, Mailgun,
 * journal) se règle dans `config/mail.php`. Le message part par la file
 * d'attente pour ne pas faire attendre la requête qui l'a déclenché.
 */
final class LaravelMailSender implements MailSenderContract
{
    public function __construct(private readonly Mailer $mailer) {}

    public function send(MailMessage $message): void
    {
        $this->mailer->to($message->to)->queue(new TransactionalMail($message));
    }
}
