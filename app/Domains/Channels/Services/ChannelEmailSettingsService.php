<?php

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\ChannelEmailSettings;

final class ChannelEmailSettingsService
{
    public function __construct(private readonly ChannelEmailSettingsRepositoryContract $settings) {}

    public function current(WorkspaceScope $scope): ChannelEmailSettings
    {
        return $this->settings->findOrCreateForWorkspace($scope->workspaceId);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(WorkspaceScope $scope, array $attributes): ChannelEmailSettings
    {
        return $this->settings->update($this->current($scope), $attributes);
    }
}
