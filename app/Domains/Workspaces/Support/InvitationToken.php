<?php

namespace App\Domains\Workspaces\Support;

/**
 * Jeton d'invitation : aléatoire, envoyé une fois par e-mail, conservé
 * seulement sous forme d'empreinte.
 */
final class InvitationToken
{
    private const BYTES = 32;

    public static function generate(): string
    {
        return bin2hex(random_bytes(self::BYTES));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
