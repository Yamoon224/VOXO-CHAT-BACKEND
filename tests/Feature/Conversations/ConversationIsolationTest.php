<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Un espace de travail ne lit ni ne modifie les conversations d'un autre. */
class ConversationIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_conversations_d_un_espace_n_apparaissent_pas_dans_un_autre(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $agentA = $this->memberOf($workspaceA, WorkspaceRole::Agent);
        Conversation::factory()->for($workspaceB)->create();

        $response = $this->actingInWorkspace($agentA, $workspaceA)
            ->getJson('/api/v1/workspace/conversations')
            ->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    #[Test]
    public function un_identifiant_de_conversation_d_un_autre_espace_est_introuvable(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $agentA = $this->memberOf($workspaceA, WorkspaceRole::Agent);
        $conversationB = Conversation::factory()->for($workspaceB)->create();

        $this->actingInWorkspace($agentA, $workspaceA)
            ->getJson("/api/v1/workspace/conversations/{$conversationB->id}")
            ->assertStatus(404);
    }
}
