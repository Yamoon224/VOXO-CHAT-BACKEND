<?php

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\DTOs\ApiKeyInfo;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lecture étroite exposée au domaine `Integrations` (lot 6, section 2.14) :
 * une clé d'API est un jeton d'accès personnel de plus, juste marqué comme
 * tel (`abilities`) pour se lister à part des sessions de connexion, et agit
 * avec les mêmes permissions que la personne qui l'a émise dans cet espace.
 */
interface ApiKeyIssuerContract
{
    /** @return string le jeton en clair, à remettre une seule fois au client */
    public function issueApiKey(User $createdBy, string $workspaceId, string $name): string;

    /** @return Collection<int, ApiKeyInfo> */
    public function listForWorkspace(string $workspaceId): Collection;

    public function revoke(string $workspaceId, string $tokenId): void;
}
