<?php

namespace App\Domains\Widget\Repositories;

use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Models\WidgetSettings;

final class EloquentWidgetSettingsRepository implements WidgetSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): WidgetSettings
    {
        return WidgetSettings::query()->firstOrCreate(['workspace_id' => $workspaceId]);
    }

    public function update(WidgetSettings $settings, array $attributes): WidgetSettings
    {
        $settings->update($attributes);

        return $settings;
    }
}
