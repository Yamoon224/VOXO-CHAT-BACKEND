<?php

namespace Tests\Unit\Channels;

use App\Domains\Channels\Services\ChannelEmailSettingsService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryChannelEmailSettingsRepository;
use Tests\TestCase;

class ChannelEmailSettingsServiceTest extends TestCase
{
    #[Test]
    public function les_reglages_se_creent_a_la_demande_pour_un_nouvel_espace(): void
    {
        $service = new ChannelEmailSettingsService(new InMemoryChannelEmailSettingsRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Owner);

        $settings = $service->current($scope);

        $this->assertSame('workspace-1', $settings->workspace_id);
        $this->assertFalse($settings->enabled);
        $this->assertNull($settings->reply_from_email);
    }

    #[Test]
    public function modifier_les_reglages_active_le_relais_et_fixe_l_adresse_d_envoi(): void
    {
        $service = new ChannelEmailSettingsService(new InMemoryChannelEmailSettingsRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Owner);

        $settings = $service->update($scope, [
            'enabled' => true,
            'reply_from_email' => 'support@exemple.fr',
            'reply_from_name' => 'Support Exemple',
        ]);

        $this->assertTrue($settings->enabled);
        $this->assertSame('support@exemple.fr', $settings->reply_from_email);
        $this->assertSame('Support Exemple', $settings->reply_from_name);
    }
}
