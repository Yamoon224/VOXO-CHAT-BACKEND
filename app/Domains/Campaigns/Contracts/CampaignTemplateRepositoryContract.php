<?php

namespace App\Domains\Campaigns\Contracts;

use App\Models\CampaignTemplate;
use Illuminate\Support\Collection;

interface CampaignTemplateRepositoryContract
{
    /** @return Collection<int, CampaignTemplate> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): CampaignTemplate;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): CampaignTemplate;

    /** @param  array<string, mixed>  $attributes */
    public function update(CampaignTemplate $template, array $attributes): CampaignTemplate;

    public function delete(CampaignTemplate $template): void;
}
