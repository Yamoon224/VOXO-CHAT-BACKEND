<?php

namespace App\Domains\Assistant\Repositories;

use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Models\AssistantSettings;

final class EloquentAssistantSettingsRepository implements AssistantSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): AssistantSettings
    {
        return AssistantSettings::query()->firstOrCreate(['workspace_id' => $workspaceId]);
    }

    public function update(AssistantSettings $settings, array $attributes): AssistantSettings
    {
        $settings->update($attributes);

        return $settings;
    }
}
