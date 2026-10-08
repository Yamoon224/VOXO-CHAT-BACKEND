<?php

namespace App\Domains\Auth\DTOs;

use App\Models\User;

/**
 * Session qui vient d'être ouverte : le jeton en clair n'existe qu'ici, le
 * temps d'être remis au client.
 */
final readonly class IssuedSession
{
    public function __construct(
        public User $user,
        public string $token,
        public ?string $workspaceId,
    ) {}
}
