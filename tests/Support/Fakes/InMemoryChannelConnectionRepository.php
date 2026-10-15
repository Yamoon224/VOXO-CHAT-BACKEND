<?php

namespace Tests\Support\Fakes;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Models\ChannelConnection;

final class InMemoryChannelConnectionRepository implements ChannelConnectionRepositoryContract
{
    /** @var array<string, ChannelConnection> */
    private array $connections = [];

    public function __construct(ChannelConnection ...$connections)
    {
        foreach ($connections as $connection) {
            $this->connections[$this->key($connection->workspace_id, $connection->channel)] = $connection;
        }
    }

    public function findOrCreateForWorkspace(string $workspaceId, string $channel): ChannelConnection
    {
        return $this->connections[$this->key($workspaceId, $channel)] ??= ModelFactory::channelConnection([
            'workspace_id' => $workspaceId,
            'channel' => $channel,
        ]);
    }

    public function findForWorkspace(string $workspaceId, string $channel): ?ChannelConnection
    {
        return $this->connections[$this->key($workspaceId, $channel)] ?? null;
    }

    public function update(ChannelConnection $connection, array $attributes): ChannelConnection
    {
        if (array_key_exists('credentials', $attributes) && is_array($attributes['credentials'])) {
            $attributes['credentials'] = json_encode($attributes['credentials']);
        }

        $connection->setRawAttributes($attributes + $connection->getAttributes(), true);

        return $connection;
    }

    private function key(string $workspaceId, string $channel): string
    {
        return "{$workspaceId}:{$channel}";
    }
}
