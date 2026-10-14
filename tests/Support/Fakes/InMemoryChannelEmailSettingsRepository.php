<?php

namespace Tests\Support\Fakes;

use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Models\ChannelEmailSettings;

final class InMemoryChannelEmailSettingsRepository implements ChannelEmailSettingsRepositoryContract
{
    /** @var array<string, ChannelEmailSettings> */
    private array $settings = [];

    public function __construct(ChannelEmailSettings ...$settings)
    {
        foreach ($settings as $setting) {
            $this->settings[$setting->workspace_id] = $setting;
        }
    }

    public function findOrCreateForWorkspace(string $workspaceId): ChannelEmailSettings
    {
        return $this->settings[$workspaceId] ??= ModelFactory::channelEmailSettings(['workspace_id' => $workspaceId]);
    }

    public function update(ChannelEmailSettings $settings, array $attributes): ChannelEmailSettings
    {
        $settings->setRawAttributes($attributes + $settings->getAttributes(), true);

        return $settings;
    }
}
