<?php

namespace App\Domains\Assistant\Repositories;

use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Models\AssistantSettings;

final class EloquentAssistantSettingsRepository implements AssistantSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): AssistantSettings
    {
        // Les valeurs par défaut sont redonnées ici explicitement : MySQL ne
        // renvoie pas la ligne après insertion, et `firstOrCreate` ne sait
        // donc pas que `enabled` vaut réellement `true` en base tant que le
        // modèle n'est pas relu — sans ça, un contrôle fait dans la même
        // requête que la création verrait `enabled` à `null` (donc `false`).
        $settings = AssistantSettings::query()->firstOrCreate(
            ['workspace_id' => $workspaceId],
            ['enabled' => true, 'confidence_threshold' => 0.60],
        );

        // Provisionné en silence au premier accès : jamais le signal d'une
        // création que l'appelant aurait demandée (pas de `201`).
        $settings->wasRecentlyCreated = false;

        return $settings;
    }

    public function update(AssistantSettings $settings, array $attributes): AssistantSettings
    {
        $settings->update($attributes);

        return $settings;
    }
}
