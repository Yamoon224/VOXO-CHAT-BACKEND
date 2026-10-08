<?php

namespace Tests\Support\Fakes;

use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Models\WidgetSettings;

final class InMemoryWidgetSettingsRepository implements WidgetSettingsRepositoryContract
{
    /** @var array<string, WidgetSettings> */
    private array $settings = [];

    public function findOrCreateForWorkspace(string $workspaceId): WidgetSettings
    {
        foreach ($this->settings as $settings) {
            if ($settings->workspace_id === $workspaceId) {
                return $settings;
            }
        }

        $settings = ModelFactory::widgetSettings(['workspace_id' => $workspaceId]);

        return $this->settings[$settings->id] = $settings;
    }

    public function update(WidgetSettings $settings, array $attributes): WidgetSettings
    {
        $settings->setRawAttributes($attributes + $settings->getAttributes(), true);

        return $settings;
    }
}
