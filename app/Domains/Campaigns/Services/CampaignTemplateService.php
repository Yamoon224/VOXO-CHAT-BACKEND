<?php

namespace App\Domains\Campaigns\Services;

use App\Domains\Campaigns\Contracts\CampaignTemplateRepositoryContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\CampaignTemplate;
use Illuminate\Support\Collection;

/** CRUD des modèles de campagne (section 2.9), réutilisés par `CampaignService::create()`. */
final class CampaignTemplateService
{
    public function __construct(private readonly CampaignTemplateRepositoryContract $templates) {}

    /** @return Collection<int, CampaignTemplate> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->templates->forWorkspace($scope->workspaceId);
    }

    /** @param  array{name: string, channel: string, subject: ?string, body: string}  $attributes */
    public function create(WorkspaceScope $scope, array $attributes): CampaignTemplate
    {
        return $this->templates->create([...$attributes, 'workspace_id' => $scope->workspaceId]);
    }

    /** @param  array{name: string, channel: string, subject: ?string, body: string}  $attributes */
    public function update(WorkspaceScope $scope, string $templateId, array $attributes): CampaignTemplate
    {
        $template = $this->templates->findInWorkspaceOrFail($scope->workspaceId, $templateId);

        return $this->templates->update($template, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $templateId): void
    {
        $this->templates->delete($this->templates->findInWorkspaceOrFail($scope->workspaceId, $templateId));
    }
}
