<?php

namespace Tests\Feature\Assistant;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistantSandboxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tester_l_agent_ia_sans_creer_de_conversation(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->aiProvider()->respondWith(new AiReply('Réponse de démonstration.', [], 0.88));

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/assistant/sandbox', ['message' => 'Quels sont vos horaires ?'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Réponse de démonstration.')
            ->assertJsonPath('data.confidence', 0.88);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/conversations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
