<?php

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Exceptions\ChannelConnectionIncompleteException;
use App\Domains\Channels\Support\MessagingChannel;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\ChannelConnection;

/** CRUD des connexions aux canaux de messagerie (lot 5), un couple (espace, canal) à la fois. */
final class ChannelConnectionService
{
    public function __construct(private readonly ChannelConnectionRepositoryContract $connections) {}

    public function current(WorkspaceScope $scope, string $channel): ChannelConnection
    {
        return $this->connections->findOrCreateForWorkspace($scope->workspaceId, $channel);
    }

    /**
     * @param  array{enabled?: bool, credentials?: array<string, mixed>}  $attributes
     *
     * @throws ChannelConnectionIncompleteException
     */
    public function update(WorkspaceScope $scope, string $channel, array $attributes): ChannelConnection
    {
        $connection = $this->current($scope, $channel);

        // Les identifiants s'ajoutent les uns aux autres au fil des mises à
        // jour (un formulaire ne renvoie que le champ qu'on vient de changer) :
        // jamais de remplacement intégral qui effacerait les autres clés déjà
        // enregistrées (ex. `verify_token`, engendré à la création).
        $credentials = [...$connection->credentials, ...($attributes['credentials'] ?? [])];
        $enabled = $attributes['enabled'] ?? $connection->enabled;

        if ($enabled) {
            $this->guardCredentialsComplete($channel, $credentials);
        }

        return $this->connections->update($connection, ['enabled' => $enabled, 'credentials' => $credentials]);
    }

    /** @param  array<string, mixed>  $credentials */
    private function guardCredentialsComplete(string $channel, array $credentials): void
    {
        foreach (MessagingChannel::from($channel)->requiredCredentialFields() as $field) {
            if (! is_string($credentials[$field] ?? null) || $credentials[$field] === '') {
                throw ChannelConnectionIncompleteException::make();
            }
        }
    }
}
