<?php

namespace App\Domains\Workspaces\Contracts;

use App\Domains\Shared\Enums\WorkspaceRole;

/**
 * Ce qu'un rôle d'espace de travail autorise.
 */
interface PermissionMatrixContract
{
    /** @return list<string> */
    public function permissionsFor(WorkspaceRole $role): array;

    public function allows(WorkspaceRole $role, string $permission): bool;
}
