<?php

namespace App\Domains\Notifications\Contracts;

use App\Domains\Notifications\DTOs\MailMessage;

/**
 * Pilote d'envoi d'e-mails. Le prestataire (Resend, Mailgun, SMTP) se choisit
 * par configuration, sans incidence sur les services qui émettent un message.
 */
interface MailSenderContract
{
    public function send(MailMessage $message): void;
}
