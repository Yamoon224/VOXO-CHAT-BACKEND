<?php

namespace App\Domains\Workspaces\Contracts;

use App\Models\WorkspaceMember;

/**
 * Ouvre un espace de travail pour un compte, qui en devient propriétaire.
 * Exposé à l'inscription, qui n'a pas à connaître le reste de la gestion des
 * espaces.
 */
interface WorkspaceProvisionerContract
{
    /** @return WorkspaceMember l'adhésion du propriétaire, espace de travail chargé */
    public function provision(string $ownerUserId, string $name): WorkspaceMember;
}
