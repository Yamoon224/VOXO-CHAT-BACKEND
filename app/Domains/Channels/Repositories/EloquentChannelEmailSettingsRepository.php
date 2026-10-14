<?php

namespace App\Domains\Channels\Repositories;

use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Models\ChannelEmailSettings;

final class EloquentChannelEmailSettingsRepository implements ChannelEmailSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): ChannelEmailSettings
    {
        // Mêmes raisons qu'`EloquentWidgetSettingsRepository::findOrCreateForWorkspace()` :
        // défauts redonnés explicitement (MySQL ne renvoie pas la ligne après
        // insertion), et la création implicite ne doit jamais se voir en 201.
        $settings = ChannelEmailSettings::query()->firstOrCreate(
            ['workspace_id' => $workspaceId],
            ['enabled' => false, 'reply_from_email' => null, 'reply_from_name' => null],
        );

        $settings->wasRecentlyCreated = false;

        return $settings;
    }

    public function update(ChannelEmailSettings $settings, array $attributes): ChannelEmailSettings
    {
        $settings->update($attributes);

        return $settings;
    }
}
