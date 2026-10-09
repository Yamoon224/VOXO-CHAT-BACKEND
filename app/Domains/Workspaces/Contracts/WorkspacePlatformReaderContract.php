<?php

namespace App\Domains\Workspaces\Contracts;

use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lecture étroite exposée à la console plateforme (lot 3) : la liste de tous
 * les espaces de travail, pour un rôle qui administre la plateforme plutôt
 * qu'un espace — jamais de droit d'écriture par ce contrat.
 */
interface WorkspacePlatformReaderContract
{
    /** @return LengthAwarePaginator<int, Workspace> */
    public function listAllPaginated(int $perPage): LengthAwarePaginator;
}
