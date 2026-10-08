<?php

namespace App\Domains\Widget\Contracts;

use App\Models\WidgetSettings;

interface WidgetSettingsRepositoryContract
{
    /** Une ligne par espace, créée au premier accès plutôt qu'à la création de l'espace. */
    public function findOrCreateForWorkspace(string $workspaceId): WidgetSettings;

    /** @param  array<string, mixed>  $attributes */
    public function update(WidgetSettings $settings, array $attributes): WidgetSettings;
}
