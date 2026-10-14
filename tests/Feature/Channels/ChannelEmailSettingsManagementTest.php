<?php

namespace Tests\Feature\Channels;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelEmailSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_modifie_les_reglages_du_canal_e_mail(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/email/settings', [
                'enabled' => true,
                'reply_from_email' => 'support@exemple.fr',
                'reply_from_name' => 'Support Exemple',
            ])
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.reply_from_email', 'support@exemple.fr')
            ->assertJsonPath('data.reply_from_name', 'Support Exemple');
    }

    #[Test]
    public function un_agent_ne_peut_pas_modifier_les_reglages_du_canal_e_mail(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/channels/email/settings')
            ->assertStatus(403);
    }

    #[Test]
    public function les_reglages_sont_desactives_par_defaut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/email/settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.reply_from_email', null);
    }
}
