<?php

namespace App\Domains\Widget\Services;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Models\WidgetSettings;

final class WidgetSettingsService
{
    public function __construct(
        private readonly WidgetSettingsRepositoryContract $settings,
        private readonly string $scriptBaseUrl,
        private readonly string $apiBaseUrl,
    ) {}

    public function current(WorkspaceScope $scope): WidgetSettings
    {
        return $this->forWorkspaceId($scope->workspaceId);
    }

    /** Accès public, sans périmètre authentifié : le widget le lit avant toute session de visiteur. */
    public function forWorkspaceId(string $workspaceId): WidgetSettings
    {
        return $this->settings->findOrCreateForWorkspace($workspaceId);
    }

    /**
     * Vérification d'installation (section 2.3) : le script ne peut
     * atteindre cette route que s'il est bien chargé sur le site du client —
     * chaque appel public des réglages vaut donc preuve de vie.
     */
    public function markInstalled(WidgetSettings $settings): void
    {
        $this->settings->update($settings, ['last_installed_check_at' => now()]);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(WorkspaceScope $scope, array $attributes): WidgetSettings
    {
        return $this->settings->update($this->current($scope), $attributes);
    }

    /**
     * Générateur de script (section 2.3) : extrait à copier-coller sur le
     * site du client. `apiBaseUrl` est l'origine de CETTE API (pas celle du
     * bundle, qui vit sur `scriptBaseUrl`) — le widget ne peut pas la
     * déduire lui-même puisqu'il tourne sur le domaine du client.
     */
    public function script(WorkspaceScope $scope): string
    {
        $workspaceId = $scope->workspaceId;

        return <<<HTML
        <script>
          (function (w, d) {
            w.VoxoWidgetConfig = { workspaceId: "{$workspaceId}", apiBaseUrl: "{$this->apiBaseUrl}" };
            var s = d.createElement("script");
            s.src = "{$this->scriptBaseUrl}/widget.js";
            s.async = true;
            d.body.appendChild(s);
          })(window, document);
        </script>
        HTML;
    }
}
