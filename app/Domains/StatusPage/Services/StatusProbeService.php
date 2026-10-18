<?php

namespace App\Domains\StatusPage\Services;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Contracts\StatusProbeCheckRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Domains\StatusPage\DTOs\ProbeCheckResult;
use App\Domains\StatusPage\Enums\ProbeStatus;
use App\Models\StatusProbe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** CRUD des sondes de disponibilité (section 2.11) ; `recordCheck()` est appelé par `CheckStatusProbeJob`, jamais par un contrôleur. */
final class StatusProbeService
{
    public function __construct(
        private readonly StatusProbeRepositoryContract $probes,
        private readonly StatusProbeCheckRepositoryContract $checks,
    ) {}

    /** @return Collection<int, StatusProbe> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->probes->forWorkspace($scope->workspaceId);
    }

    /**
     * @param  array{name: string, url: string, check_interval_minutes: int}  $attributes
     *
     * `status` posé ici, jamais laissé au défaut de la colonne : l'objet
     * renvoyé à la réponse HTTP reflète l'état d'insertion, que la ligne
     * vienne ou non d'être rechargée depuis la base.
     */
    public function create(WorkspaceScope $scope, array $attributes): StatusProbe
    {
        return $this->probes->create([...$attributes, 'workspace_id' => $scope->workspaceId, 'status' => ProbeStatus::Unknown]);
    }

    /** @param  array{name: string, url: string, check_interval_minutes: int}  $attributes */
    public function update(WorkspaceScope $scope, string $probeId, array $attributes): StatusProbe
    {
        $probe = $this->probes->findInWorkspaceOrFail($scope->workspaceId, $probeId);

        return $this->probes->update($probe, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $probeId): void
    {
        $this->probes->delete($this->probes->findInWorkspaceOrFail($scope->workspaceId, $probeId));
    }

    /** Taux de disponibilité sur les 30 derniers jours, pour la page publique comme pour le réglage de l'espace. */
    public function uptimeRatio(string $probeId): ?float
    {
        return $this->checks->uptimeRatio($probeId, Carbon::now()->subDays(30));
    }

    public function recordCheck(StatusProbe $probe, ProbeCheckResult $result): void
    {
        $this->checks->create([
            'workspace_id' => $probe->workspace_id,
            'probe_id' => $probe->id,
            'status' => $result->isUp ? ProbeStatus::Up : ProbeStatus::Down,
            'response_time_ms' => $result->responseTimeMs,
            'checked_at' => now(),
        ]);

        $this->probes->update($probe, [
            'status' => $result->isUp ? ProbeStatus::Up : ProbeStatus::Down,
            'last_checked_at' => now(),
            'consecutive_failures' => $result->isUp ? 0 : $probe->consecutive_failures + 1,
        ]);
    }
}
