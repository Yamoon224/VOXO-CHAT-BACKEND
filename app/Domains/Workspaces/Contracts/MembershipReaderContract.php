<?php

namespace App\Domains\Workspaces\Contracts;

use App\Models\WorkspaceMember;
use Illuminate\Support\Collection;

/**
 * Lecture étroite des adhésions, exposée aux domaines qui ont besoin de
 * savoir « où ce compte agit-il ? » sans pouvoir modifier une équipe.
 */
interface MembershipReaderContract
{
    /**
     * Adhésions d'un compte, espace de travail chargé, de la plus ancienne à
     * la plus récente.
     *
     * @return Collection<int, WorkspaceMember>
     */
    public function forUser(string $userId): Collection;

    /** Adhésion du compte à cet espace, espace de travail chargé. */
    public function findForUser(string $workspaceId, string $userId): ?WorkspaceMember;
}
