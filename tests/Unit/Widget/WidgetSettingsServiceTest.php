<?php

namespace Tests\Unit\Widget;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Widget\Services\WidgetSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryWidgetSettingsRepository;
use Tests\TestCase;

class WidgetSettingsServiceTest extends TestCase
{
    #[Test]
    public function le_script_porte_l_identifiant_de_l_espace_et_l_url_configuree(): void
    {
        $service = new WidgetSettingsService(new InMemoryWidgetSettingsRepository, 'https://cdn.voxo.test');
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Owner);

        $script = $service->script($scope);

        $this->assertStringContainsString('workspace-1', $script);
        $this->assertStringContainsString('https://cdn.voxo.test/widget.js', $script);
    }

    #[Test]
    public function les_reglages_se_creent_a_la_demande_pour_un_nouvel_espace(): void
    {
        $service = new WidgetSettingsService(new InMemoryWidgetSettingsRepository, 'https://cdn.voxo.test');
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Owner);

        $settings = $service->current($scope);

        $this->assertSame('workspace-1', $settings->workspace_id);
        $this->assertSame('#4F46E5', $settings->primary_color);
    }
}
