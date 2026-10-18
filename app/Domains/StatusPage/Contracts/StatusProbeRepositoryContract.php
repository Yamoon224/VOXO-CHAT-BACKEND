<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusProbe;
use Illuminate\Support\Collection;

interface StatusProbeRepositoryContract
{
    /** @return Collection<int, StatusProbe> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): StatusProbe;

    /** Sans portée d'espace : pour `CheckStatusProbeJob`, qui ne connaît que l'identifiant mis en file. */
    public function findById(string $id): ?StatusProbe;

    /** @return Collection<int, StatusProbe> Toutes celles dont l'intervalle est écoulé, tous espaces confondus — pour la commande planifiée. */
    public function dueForCheck(): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusProbe;

    /** @param  array<string, mixed>  $attributes */
    public function update(StatusProbe $probe, array $attributes): StatusProbe;

    public function delete(StatusProbe $probe): void;
}
