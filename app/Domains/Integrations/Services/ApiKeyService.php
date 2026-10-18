<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Auth\Contracts\ApiKeyIssuerContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Émission et révocation des clés d'API (section 2.14), par
 * `ApiKeyIssuerContract` (domaine `Auth`) — la lecture étroite que ce
 * contrat expose se consomme sans jamais nommer son DTO (`ApiKeyInfo` reste
 * un détail du domaine `Auth`), comme `AssistantService` le fait déjà pour
 * `KnowledgeSearchContract`.
 */
final class ApiKeyService
{
    public function __construct(private readonly ApiKeyIssuerContract $apiKeys) {}

    /** @return Collection<int, mixed> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->apiKeys->listForWorkspace($scope->workspaceId);
    }

    /** @return string le jeton en clair, à remettre une seule fois au client */
    public function issue(WorkspaceScope $scope, User $createdBy, string $name): string
    {
        return $this->apiKeys->issueApiKey($createdBy, $scope->workspaceId, $name);
    }

    public function revoke(WorkspaceScope $scope, string $tokenId): void
    {
        $this->apiKeys->revoke($scope->workspaceId, $tokenId);
    }
}
