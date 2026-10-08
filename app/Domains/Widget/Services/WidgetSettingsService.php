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

    /** @param  array<string, mixed>  $attributes */
    public function update(WorkspaceScope $scope, array $attributes): WidgetSettings
    {
        return $this->settings->update($this->current($scope), $attributes);
    }

    /**
     * Générateur de script (section 2.3) : extrait à copier-coller sur le
     * site du client. Pas encore de bundle `widget.js` embarquable (voir la
     * note de portée du lot 2) — seul le texte du script est généré ici.
     */
    public function script(WorkspaceScope $scope): string
    {
        $workspaceId = $scope->workspaceId;

        return <<<HTML
        <script>
          (function (w, d) {
            w.VoxoWidgetConfig = { workspaceId: "{$workspaceId}" };
            var s = d.createElement("script");
            s.src = "{$this->scriptBaseUrl}/widget.js";
            s.async = true;
            d.body.appendChild(s);
          })(window, document);
        </script>
        HTML;
    }
}
