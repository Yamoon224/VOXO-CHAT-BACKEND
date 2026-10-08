<?php

namespace App\Domains\Auth\Contracts;

use App\Models\User;

/**
 * Cycle de vie des jetons d'accès.
 *
 * Le jeton porte l'espace de travail de la session : c'est le seul endroit où
 * ce lien s'écrit.
 */
interface AccessTokenManagerContract
{
    /** @return string le jeton en clair, à remettre une seule fois au client */
    public function issue(User $user, ?string $workspaceId, string $deviceName): string;

    /** Espace de travail porté par le jeton de la requête en cours. */
    public function currentWorkspaceId(User $user): ?string;

    /** Bascule le jeton de la requête en cours sur un autre espace. */
    public function bindCurrentToWorkspace(User $user, string $workspaceId): void;

    public function revokeCurrent(User $user): void;

    /** Révoque toutes les sessions du compte, par exemple après un changement de mot de passe oublié. */
    public function revokeAll(User $user): void;
}
