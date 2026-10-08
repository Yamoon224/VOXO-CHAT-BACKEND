<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\DTOs\SessionView;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Domains\Workspaces\Contracts\PermissionMatrixContract;
use App\Models\User;

/**
 * Décrit la session d'un compte : espace courant, rôle, permissions.
 *
 * Les permissions sont servies à plat plutôt que déduites des rôles côté
 * client : elles servent à masquer ce qui serait refusé, l'autorisation
 * réelle restant appliquée sur chaque route.
 */
final class SessionService
{
    public function __construct(
        private readonly MembershipReaderContract $memberships,
        private readonly PermissionMatrixContract $matrix,
        private readonly AccessTokenManagerContract $tokens,
    ) {}

    /** La session portée par le jeton de la requête en cours. */
    public function current(User $user): SessionView
    {
        return $this->describe($user, $this->tokens->currentWorkspaceId($user));
    }

    public function describe(User $user, ?string $workspaceId): SessionView
    {
        // Une seule lecture sert à la fois la liste des espaces et l'espace
        // courant.
        $memberships = $this->memberships->forUser($user->id);
        $current = $workspaceId === null ? null : $memberships->firstWhere('workspace_id', $workspaceId);

        return new SessionView(
            $user,
            $current,
            $memberships,
            $current === null ? [] : $this->matrix->permissionsFor($current->role),
            $user->hasRole(User::PLATFORM_ADMIN_ROLE),
        );
    }
}
