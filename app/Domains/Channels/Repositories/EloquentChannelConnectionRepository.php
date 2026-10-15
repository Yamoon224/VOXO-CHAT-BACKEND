<?php

namespace App\Domains\Channels\Repositories;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Support\MessagingChannel;
use App\Models\ChannelConnection;
use Illuminate\Support\Str;

final class EloquentChannelConnectionRepository implements ChannelConnectionRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId, string $channel): ChannelConnection
    {
        $connection = ChannelConnection::query()->firstOrCreate(
            ['workspace_id' => $workspaceId, 'channel' => $channel],
            ['enabled' => false, 'credentials' => $this->defaultCredentials($channel)],
        );

        // Même garde qu'`EloquentChannelEmailSettingsRepository::findOrCreateForWorkspace()` :
        // MySQL ne renvoie pas la ligne après insertion, et la création
        // implicite ne doit jamais se voir en 201.
        $connection->wasRecentlyCreated = false;

        return $connection;
    }

    public function findForWorkspace(string $workspaceId, string $channel): ?ChannelConnection
    {
        return ChannelConnection::query()
            ->where('workspace_id', $workspaceId)
            ->where('channel', $channel)
            ->first();
    }

    public function update(ChannelConnection $connection, array $attributes): ChannelConnection
    {
        $connection->update($attributes);

        return $connection;
    }

    /**
     * Jeton de vérification du webhook Meta (WhatsApp/Messenger/Instagram) :
     * engendré une fois à la création, à copier dans la console de l'app —
     * sans quoi Meta refuse d'enregistrer l'URL du webhook.
     *
     * @return array<string, mixed>
     */
    private function defaultCredentials(string $channel): array
    {
        $messagingChannel = MessagingChannel::from($channel);
        $credentials = [];

        if ($messagingChannel->usesMetaWebhookHandshake()) {
            $credentials['verify_token'] = Str::random(32);
        }

        if ($messagingChannel->usesSecretToken()) {
            $credentials['secret_token'] = Str::random(32);
        }

        return $credentials;
    }
}
