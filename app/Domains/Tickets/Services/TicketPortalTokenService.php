<?php

namespace App\Domains\Tickets\Services;

use App\Domains\Tickets\Exceptions\TicketPortalTokenInvalidException;
use App\Domains\Tickets\Support\TicketPortalToken;

final class TicketPortalTokenService
{
    public function __construct(private readonly TicketPortalToken $tokens) {}

    public function issue(string $workspaceId, string $contactId): string
    {
        return $this->tokens->issue($workspaceId, $contactId, now());
    }

    /**
     * @return array{workspaceId: string, contactId: string}
     *
     * @throws TicketPortalTokenInvalidException
     */
    public function verify(string $token): array
    {
        return $this->tokens->verify($token, now()) ?? throw TicketPortalTokenInvalidException::make();
    }
}
