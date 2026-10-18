<?php

namespace App\Domains\Notifications\Senders;

use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Notifications\DTOs\MailMessage;
use App\Domains\Notifications\Enums\MailTemplate;

/**
 * Conserve les envois en mémoire : pilote de la suite de tests, où aucun
 * prestataire n'est jamais appelé.
 */
final class ArrayMailSender implements MailSenderContract
{
    /** @var list<MailMessage> */
    private array $sent = [];

    /** @var list<array{to: string, subject: string, bodyHtml: string}> */
    private array $sentRaw = [];

    public function send(MailMessage $message): void
    {
        $this->sent[] = $message;
    }

    public function sendRaw(string $to, string $subject, string $bodyHtml): void
    {
        $this->sentRaw[] = ['to' => $to, 'subject' => $subject, 'bodyHtml' => $bodyHtml];
    }

    /** @return list<MailMessage> */
    public function sent(): array
    {
        return $this->sent;
    }

    /** @return list<array{to: string, subject: string, bodyHtml: string}> */
    public function sentRaw(): array
    {
        return $this->sentRaw;
    }

    /** Dernier message d'un modèle donné envoyé à cette adresse. */
    public function lastTo(string $email, MailTemplate $template): ?MailMessage
    {
        foreach (array_reverse($this->sent) as $message) {
            if ($message->to === $email && $message->template === $template) {
                return $message;
            }
        }

        return null;
    }
}
