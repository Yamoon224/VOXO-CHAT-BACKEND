<?php

namespace App\Domains\Shared\Support;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Exceptions\WorkspaceRequiredException;
use App\Domains\Shared\Exceptions\WorkspaceScopeViolationException;
use Illuminate\Http\Request;

/**
 * Périmètre d'espace de travail d'un appelant.
 *
 * L'isolation entre clients est la règle de sécurité la plus sensible de la
 * plateforme. Elle est résolue une seule fois par requête, à partir du jeton
 * de l'appelant, puis transportée par cet objet jusqu'aux services et aux
 * dépôts.
 *
 * `workspace_id` n'est jamais lu dans la requête : un filtre de sécurité que
 * le client peut choisir n'est qu'un paramètre d'affichage.
 */
final readonly class WorkspaceScope
{
    public function __construct(
        public string $workspaceId,
        public string $memberId,
        public string $userId,
        public WorkspaceRole $role,
    ) {}

    /** @throws WorkspaceRequiredException si aucun périmètre n'a été résolu pour la requête. */
    public static function fromRequest(Request $request): self
    {
        $scope = $request->attributes->get(self::class);

        if (! $scope instanceof self) {
            throw WorkspaceRequiredException::make();
        }

        return $scope;
    }

    public function attachTo(Request $request): void
    {
        $request->attributes->set(self::class, $this);
    }

    /**
     * Ajoute la borne d'espace de travail à un jeu de filtres, en écrasant
     * toute valeur venue de la requête.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function apply(array $filters): array
    {
        $filters['workspace_id'] = $this->workspaceId;

        return $filters;
    }

    public function owns(?string $workspaceId): bool
    {
        return $workspaceId !== null && $workspaceId === $this->workspaceId;
    }

    /** @throws WorkspaceScopeViolationException */
    public function assertOwns(?string $workspaceId): void
    {
        if (! $this->owns($workspaceId)) {
            throw WorkspaceScopeViolationException::make();
        }
    }
}
