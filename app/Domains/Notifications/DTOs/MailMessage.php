<?php

namespace App\Domains\Notifications\DTOs;

use App\Domains\Notifications\Enums\MailTemplate;

/**
 * E-mail transactionnel prêt à partir, indépendant du pilote d'envoi.
 */
final readonly class MailMessage
{
    /** @param  array<string, mixed>  $data  variables de la vue */
    public function __construct(
        public string $to,
        public MailTemplate $template,
        public array $data = [],
    ) {}

    public function subject(): string
    {
        return $this->template->subject($this->data);
    }
}
