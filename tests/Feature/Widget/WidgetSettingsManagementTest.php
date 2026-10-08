<?php

namespace Tests\Feature\Widget;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WidgetSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_modifie_les_reglages_du_widget(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/widget/settings', [
                'primary_color' => '#112233',
                'welcome_message' => 'Bienvenue chez nous !',
            ])
            ->assertOk()
            ->assertJsonPath('data.primary_color', '#112233')
            ->assertJsonPath('data.welcome_message', 'Bienvenue chez nous !');
    }

    #[Test]
    public function un_agent_ne_peut_pas_modifier_les_reglages_du_widget(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/widget/settings')
            ->assertStatus(403);
    }

    #[Test]
    public function generer_le_script_d_integration(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/widget/script')
            ->assertOk();

        $this->assertStringContainsString($workspace->id, $response->json('data.script'));
    }
}
