<?php

namespace App\Domains\Widget\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Identité minimale d'un visiteur anonyme pour le garde `visitor` (temps
 * réel, section 4.1) — jamais persistée, reconstruite à chaque requête
 * d'authentification de canal à partir du jeton de session
 * (`VisitorSessionToken`). Pas de mot de passe, pas de « remember me » : un
 * visiteur anonyme n'a rien de tout cela.
 */
final readonly class VisitorPrincipal implements Authenticatable
{
    public function __construct(
        public string $workspaceId,
        public string $visitorId,
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'visitor_id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->visitorId;
    }

    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }

    /** Lu par `PusherBroadcaster::validAuthenticationResponse()` pour l'identifiant de présence. */
    public function getAuthIdentifierForBroadcasting(): string
    {
        return "visitor:{$this->visitorId}";
    }
}
