<?php

namespace Tests\Feature\Conversations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_repond_a_une_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($agent, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/messages", ['body' => 'Bonjour, comment puis-je vous aider ?'])
            ->assertCreated()
            ->assertJsonPath('data.sender_type', 'agent')
            ->assertJsonPath('data.visibility', 'public');
    }

    #[Test]
    public function une_note_interne_n_est_pas_visible_du_visiteur(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($agent, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/messages", [
                'body' => 'Ce client a déjà contacté deux fois.',
                'visibility' => 'internal',
            ])
            ->assertCreated()
            ->assertJsonPath('data.visibility', 'internal');

        $messages = $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/conversations/{$conversation->id}/messages")
            ->assertOk();

        $this->assertCount(1, $messages->json('data'));
    }

    #[Test]
    public function un_lecteur_ne_peut_pas_repondre(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        $conversation = Conversation::factory()->for($workspace)->create();

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson("/api/v1/workspace/conversations/{$conversation->id}/messages", ['body' => 'Je tente malgré tout.'])
            ->assertStatus(403);
    }
}
