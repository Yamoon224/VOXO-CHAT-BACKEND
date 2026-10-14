<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\EmailChannelProviderContract;

/** Conserve les envois en mémoire : pilote de la suite de tests, aucun mailer réel n'est jamais appelé. */
final class ArrayEmailChannelProvider implements EmailChannelProviderContract
{
    /** @var list<array{to: string, from_email: string, from_name: string, subject: string, body: string}> */
    private array $sent = [];

    public function sendReply(string $toEmail, string $fromEmail, string $fromName, string $subject, string $body): void
    {
        $this->sent[] = ['to' => $toEmail, 'from_email' => $fromEmail, 'from_name' => $fromName, 'subject' => $subject, 'body' => $body];
    }

    /** @return list<array{to: string, from_email: string, from_name: string, subject: string, body: string}> */
    public function sent(): array
    {
        return $this->sent;
    }
}
