<?php

namespace Tests\Feature\Automations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\AutomationScenario;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationScenarioTestEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tester_un_scenario_ne_cree_aucune_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $scenario = AutomationScenario::factory()->create([
            'workspace_id' => $workspace->id,
            'conditions' => [],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
        ]);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/automations/{$scenario->id}/test", ['message' => 'Bonjour.'])
            ->assertOk()
            ->assertJsonPath('data.conditions_matched', true)
            ->assertJsonPath('data.steps.0.type', 'send_message');

        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('messages', 0);
    }

    #[Test]
    public function tester_un_scenario_s_arrete_a_la_premiere_question(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $scenario = AutomationScenario::factory()->create([
            'workspace_id' => $workspace->id,
            'conditions' => [],
            'steps' => [
                ['type' => 'ask_question', 'config' => ['body' => 'Votre e-mail ?', 'attribute_key' => 'email']],
                ['type' => 'send_message', 'config' => ['body' => 'Jamais atteint.']],
            ],
        ]);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson("/api/v1/workspace/automations/{$scenario->id}/test", ['message' => 'Bonjour.'])
            ->assertOk();

        $this->assertCount(1, $response->json('data.steps'));
    }
}
