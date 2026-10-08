<?php

namespace Tests\Support\Fakes;

use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Models\AssistantSettings;

final class InMemoryAssistantSettingsRepository implements AssistantSettingsRepositoryContract
{
    /** @var array<string, AssistantSettings> */
    private array $settings = [];

    public function findOrCreateForWorkspace(string $workspaceId): AssistantSettings
    {
        foreach ($this->settings as $settings) {
            if ($settings->workspace_id === $workspaceId) {
                return $settings;
            }
        }

        $settings = ModelFactory::assistantSettings(['workspace_id' => $workspaceId]);

        return $this->settings[$settings->id] = $settings;
    }

    public function update(AssistantSettings $settings, array $attributes): AssistantSettings
    {
        $settings->setRawAttributes($attributes + $settings->getAttributes(), true);

        return $settings;
    }
}
