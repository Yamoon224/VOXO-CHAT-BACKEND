<?php

namespace App\Domains\Campaigns\Contracts;

use App\Models\Campaign;
use Illuminate\Support\Collection;

interface CampaignRepositoryContract
{
    /** @return Collection<int, Campaign> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Campaign;

    /** Sans portée d'espace : pour `SendCampaignMessageJob`, qui ne connaît que l'identifiant mis en file. */
    public function findById(string $id): ?Campaign;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Campaign;

    /** @param  array<string, mixed>  $attributes */
    public function update(Campaign $campaign, array $attributes): Campaign;

    public function delete(Campaign $campaign): void;
}
