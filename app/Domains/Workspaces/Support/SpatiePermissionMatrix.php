<?php

namespace App\Domains\Workspaces\Support;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Contracts\PermissionMatrixContract;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matrice des droits lue dans les rôles de spatie/laravel-permission.
 *
 * La lecture passe par le registre du paquet, qui met la matrice en cache :
 * vérifier une permission ne coûte aucune requête une fois le cache chaud.
 */
final class SpatiePermissionMatrix implements PermissionMatrixContract
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function permissionsFor(WorkspaceRole $role): array
    {
        $permissions = [];

        /** @var Permission $permission */
        foreach ($this->registrar->getPermissions() as $permission) {
            if ($permission->roles->contains('name', $role->value)) {
                $permissions[] = $permission->name;
            }
        }

        sort($permissions);

        return $permissions;
    }

    public function allows(WorkspaceRole $role, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($role), true);
    }
}
