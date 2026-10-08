<?php

namespace App\Domains\Assistant\Services;

use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\AssistantSettings;

final class AssistantSettingsService
{
    public function __construct(private readonly AssistantSettingsRepositoryContract $settings) {}

    public function current(WorkspaceScope $scope): AssistantSettings
    {
        return $this->settings->findOrCreateForWorkspace($scope->workspaceId);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(WorkspaceScope $scope, array $attributes): AssistantSettings
    {
        return $this->settings->update($this->current($scope), $attributes);
    }
}
