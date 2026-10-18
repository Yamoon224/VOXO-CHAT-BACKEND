<?php

namespace App\Domains\Campaigns\Repositories;

use App\Domains\Campaigns\Contracts\CampaignTemplateRepositoryContract;
use App\Models\CampaignTemplate;
use Illuminate\Support\Collection;

final class EloquentCampaignTemplateRepository implements CampaignTemplateRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return CampaignTemplate::query()->where('workspace_id', $workspaceId)->latest('created_at')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): CampaignTemplate
    {
        return CampaignTemplate::query()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function create(array $attributes): CampaignTemplate
    {
        return CampaignTemplate::query()->create($attributes);
    }

    public function update(CampaignTemplate $template, array $attributes): CampaignTemplate
    {
        $template->update($attributes);

        return $template;
    }

    public function delete(CampaignTemplate $template): void
    {
        $template->delete();
    }
}
