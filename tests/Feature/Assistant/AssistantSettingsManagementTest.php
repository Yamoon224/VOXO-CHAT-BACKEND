<?php

namespace Tests\Feature\Assistant;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistantSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_modifie_le_seuil_de_confiance_et_les_consignes(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/assistant/settings', [
                'tone_instructions' => 'Sois bref et chaleureux.',
                'confidence_threshold' => 0.8,
            ])
            ->assertOk()
            ->assertJsonPath('data.tone_instructions', 'Sois bref et chaleureux.')
            ->assertJsonPath('data.confidence_threshold', 0.8);
    }

    #[Test]
    public function un_agent_n_a_pas_acces_aux_reglages_de_l_agent_ia(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/assistant/settings')
            ->assertStatus(403);
    }
}
