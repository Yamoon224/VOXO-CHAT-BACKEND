<?php

namespace App\Domains\Assistant\Services;

use App\Domains\Assistant\Contracts\AgentToolRepositoryContract;
use App\Domains\Assistant\Contracts\AvailableToolsContract;
use App\Domains\Assistant\Contracts\ToolCallerContract;
use App\Domains\Assistant\Contracts\ToolInvokerContract;
use App\Domains\Assistant\DTOs\ToolDefinition;
use App\Domains\Assistant\DTOs\ToolParameter;
use App\Domains\Assistant\Exceptions\ToolCallFailedException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\AgentTool;
use Illuminate\Support\Collection;

/**
 * CRUD des outils déclarés (section 2.2, P3) et pont vers `ToolCallerContract`
 * pour `ClaudeAiProvider`, via `ToolInvokerContract` — jamais l'inverse : le
 * fournisseur d'IA ne connaît ni l'URL ni les en-têtes d'un outil.
 */
final class AgentToolService implements AvailableToolsContract, ToolInvokerContract
{
    public function __construct(
        private readonly AgentToolRepositoryContract $tools,
        private readonly ToolCallerContract $caller,
    ) {}

    /** @return Collection<int, AgentTool> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->tools->forWorkspace($scope->workspaceId);
    }

    /**
     * @param  array{name: string, description: string, http_method: string, url: string, headers: ?array<string, string>, parameters: list<array{name: string, type: string, description: string, required: bool, in: string}>, enabled: bool}  $attributes
     */
    public function create(WorkspaceScope $scope, array $attributes): AgentTool
    {
        return $this->tools->create([...$attributes, 'workspace_id' => $scope->workspaceId]);
    }

    /**
     * Les en-têtes s'ajoutent les uns aux autres au fil des mises à jour,
     * jamais un remplacement intégral (même raison que
     * `ChannelConnectionService::update()`) : un formulaire qui affiche des
     * en-têtes masqués ne peut renvoyer que celui qu'on vient de changer.
     *
     * @param  array{name: string, description: string, http_method: string, url: string, headers: ?array<string, string>, parameters: list<array{name: string, type: string, description: string, required: bool, in: string}>, enabled: bool}  $attributes
     */
    public function update(WorkspaceScope $scope, string $toolId, array $attributes): AgentTool
    {
        $tool = $this->tools->findInWorkspaceOrFail($scope->workspaceId, $toolId);
        $headers = [...($tool->headers ?? []), ...($attributes['headers'] ?? [])];

        return $this->tools->update($tool, [...$attributes, 'headers' => $headers === [] ? null : $headers]);
    }

    public function delete(WorkspaceScope $scope, string $toolId): void
    {
        $this->tools->delete($this->tools->findInWorkspaceOrFail($scope->workspaceId, $toolId));
    }

    /** @return list<ToolDefinition> Ce que `ClaudeAiProvider` propose à Claude — jamais l'URL ni les en-têtes. */
    public function availableToolDefinitions(string $workspaceId): array
    {
        return $this->tools->enabledForWorkspace($workspaceId)
            ->map(fn (AgentTool $tool) => new ToolDefinition(
                name: $tool->name,
                description: $tool->description,
                parameters: array_map(
                    fn (array $parameter) => new ToolParameter(
                        name: $parameter['name'],
                        type: $parameter['type'],
                        description: $parameter['description'],
                        required: $parameter['required'],
                    ),
                    $tool->parameters,
                ),
            ))
            ->all();
    }

    /**
     * Implémente `ToolInvokerContract` : jamais d'exception — un outil
     * introuvable, désactivé ou en échec renvoie un message que Claude peut
     * relayer au visiteur, plutôt que de faire échouer toute la réponse.
     */
    public function invoke(string $workspaceId, string $toolName, array $arguments): string
    {
        $tool = $this->tools->findByName($workspaceId, $toolName);

        if ($tool === null || ! $tool->enabled) {
            return "Cet outil n'est pas disponible.";
        }

        try {
            return $this->caller->call($tool, $arguments);
        } catch (ToolCallFailedException $exception) {
            return $exception->getMessage();
        }
    }
}
