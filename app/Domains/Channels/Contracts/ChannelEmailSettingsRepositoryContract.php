<?php

namespace App\Domains\Channels\Contracts;

use App\Models\ChannelEmailSettings;

interface ChannelEmailSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): ChannelEmailSettings;

    /** @param  array<string, mixed>  $attributes */
    public function update(ChannelEmailSettings $settings, array $attributes): ChannelEmailSettings;
}
