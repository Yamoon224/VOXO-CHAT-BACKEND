<?php

namespace App\Domains\Assistant\Contracts;

use App\Models\AssistantSettings;

interface AssistantSettingsRepositoryContract
{
    /** Une ligne par espace, créée au premier accès plutôt qu'à la création de l'espace. */
    public function findOrCreateForWorkspace(string $workspaceId): AssistantSettings;

    /** @param  array<string, mixed>  $attributes */
    public function update(AssistantSettings $settings, array $attributes): AssistantSettings;
}
