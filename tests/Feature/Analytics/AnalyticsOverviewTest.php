<?php

namespace Tests\Feature\Analytics;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsOverviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_consulte_la_vue_d_ensemble_de_l_activite(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'rating' => 5]);
        Message::factory()->create(['conversation_id' => $conversation->id, 'workspace_id' => $workspace->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk()
            ->assertJsonPath('data.conversation_count', 1)
            ->assertJsonPath('data.message_count', 1)
            ->assertJsonPath('data.average_rating', 5);
    }

    #[Test]
    public function l_activite_d_un_autre_espace_n_est_jamais_comptee(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $agentA = $this->memberOf($workspaceA, WorkspaceRole::Agent);
        Conversation::factory()->create(['workspace_id' => $workspaceB->id]);

        $this->actingInWorkspace($agentA, $workspaceA)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk()
            ->assertJsonPath('data.conversation_count', 0);
    }
}
