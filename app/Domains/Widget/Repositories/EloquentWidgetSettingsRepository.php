<?php

namespace App\Domains\Widget\Repositories;

use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Domains\Widget\Enums\WidgetPosition;
use App\Models\WidgetSettings;

final class EloquentWidgetSettingsRepository implements WidgetSettingsRepositoryContract
{
    public function findOrCreateForWorkspace(string $workspaceId): WidgetSettings
    {
        // Les valeurs par défaut sont redonnées ici explicitement : MySQL ne
        // renvoie pas la ligne après insertion, donc `firstOrCreate` ne sait
        // pas que `position`/`primary_color`/`language` ont réellement une
        // valeur en base tant que le modèle n'est pas relu — sans ça, un
        // contrôle fait dans la même requête que la création verrait
        // `position` à `null` plutôt qu'à l'enum attendu.
        $settings = WidgetSettings::query()->firstOrCreate(
            ['workspace_id' => $workspaceId],
            ['primary_color' => '#4F46E5', 'position' => WidgetPosition::BottomRight, 'language' => 'fr'],
        );

        // Provisionné en silence au premier accès : jamais le signal d'une
        // création que l'appelant aurait demandée (pas de `201`).
        $settings->wasRecentlyCreated = false;

        return $settings;
    }

    public function update(WidgetSettings $settings, array $attributes): WidgetSettings
    {
        $settings->update($attributes);

        return $settings;
    }
}
