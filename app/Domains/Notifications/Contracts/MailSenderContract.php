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

    /**
     * Contenu libre, sans vue Blade ni `MailTemplate` : pour un domaine qui
     * compose lui-même son objet et son corps (ex. `Campaigns`, section 2.9),
     * là où les e-mails transactionnels de `send()` restent un ensemble fermé
     * de modèles connus à l'avance.
     */
    public function sendRaw(string $to, string $subject, string $bodyHtml): void;
}
