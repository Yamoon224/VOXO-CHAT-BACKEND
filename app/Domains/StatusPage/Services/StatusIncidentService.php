<?php

namespace App\Domains\StatusPage\Services;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Contracts\StatusIncidentRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusIncidentUpdateRepositoryContract;
use App\Domains\StatusPage\Enums\IncidentStatus;
use App\Domains\StatusPage\Notifications\StatusAlertNotifier;
use App\Models\StatusIncident;
use App\Models\StatusIncidentUpdate;
use Illuminate\Support\Collection;

/** CRUD des incidents et de leur chronologie publique (section 2.11) ; notifie les abonnés à chaque mise à jour. */
final class StatusIncidentService
{
    public function __construct(
        private readonly StatusIncidentRepositoryContract $incidents,
        private readonly StatusIncidentUpdateRepositoryContract $updates,
        private readonly StatusAlertNotifier $alerts,
    ) {}

    /** @return Collection<int, StatusIncident> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->incidents->forWorkspace($scope->workspaceId);
    }

    /** @return Collection<int, StatusIncidentUpdate> */
    public function updatesFor(WorkspaceScope $scope, string $incidentId): Collection
    {
        $this->incidents->findInWorkspaceOrFail($scope->workspaceId, $incidentId);

        return $this->updates->forIncident($incidentId);
    }

    /**
     * @param  array{title: string, impact: string, status: string, body: string}  $attributes
     *
     * Toujours accompagnée d'un premier message : une page de statut sans
     * aucune explication n'informe personne.
     */
    public function create(WorkspaceScope $scope, array $attributes): StatusIncident
    {
        $incident = $this->incidents->create([
            'workspace_id' => $scope->workspaceId,
            'title' => $attributes['title'],
            'impact' => $attributes['impact'],
            'status' => $attributes['status'],
            'started_at' => now(),
        ]);

        $this->recordUpdate($incident, $attributes['status'], $attributes['body']);

        return $incident;
    }

    /** @param  array{status: string, body: string}  $attributes */
    public function addUpdate(WorkspaceScope $scope, string $incidentId, array $attributes): StatusIncident
    {
        $incident = $this->incidents->findInWorkspaceOrFail($scope->workspaceId, $incidentId);

        $this->recordUpdate($incident, $attributes['status'], $attributes['body']);

        return $this->incidents->update($incident, [
            'status' => $attributes['status'],
            'resolved_at' => $attributes['status'] === IncidentStatus::Resolved->value ? now() : $incident->resolved_at,
        ]);
    }

    public function delete(WorkspaceScope $scope, string $incidentId): void
    {
        $this->incidents->delete($this->incidents->findInWorkspaceOrFail($scope->workspaceId, $incidentId));
    }

    private function recordUpdate(StatusIncident $incident, string $status, string $body): void
    {
        $update = $this->updates->create([
            'workspace_id' => $incident->workspace_id,
            'incident_id' => $incident->id,
            'status' => $status,
            'body' => $body,
        ]);

        $this->alerts->notifyIncidentUpdate($incident, $update);
    }
}
