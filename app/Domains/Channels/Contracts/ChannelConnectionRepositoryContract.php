<?php

namespace App\Domains\Channels\Contracts;

use App\Models\ChannelConnection;

interface ChannelConnectionRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId, string $channel): ChannelConnection;

    /** `null` si l'espace n'a jamais configuré ce canal — distinct de « configuré mais désactivé ». */
    public function findForWorkspace(string $workspaceId, string $channel): ?ChannelConnection;

    /** @param  array<string, mixed>  $attributes */
    public function update(ChannelConnection $connection, array $attributes): ChannelConnection;
}
