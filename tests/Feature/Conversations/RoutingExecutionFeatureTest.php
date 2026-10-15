<?php

namespace Tests\Feature\Conversations;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutingExecutionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_nouveau_visiteur_est_affecte_selon_la_regle_de_routage_active(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $billingAgent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $this->aiProvider()->respondWith(new AiReply('Je transmets à un coéquipier.', [], 0.1));

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/routing-rules', [
                'name' => 'Facturation',
                'conditions' => [['field' => 'message', 'operator' => 'contains', 'value' => 'facture']],
                'action_type' => 'assign_user',
                'assignee_user_ids' => [$billingAgent->id],
            ])
            ->assertCreated();

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $sent = $this->postJson('/api/v1/public/widget/messages', [
            'token' => $token,
            'body' => "J'ai un souci avec ma facture de ce mois-ci.",
        ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/conversations/{$sent->json('data.conversation_id')}")
            ->assertOk()
            ->assertJsonPath('data.assigned_user.id', $billingAgent->id);
    }

    #[Test]
    public function sans_regle_correspondante_la_conversation_reste_non_affectee(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $this->aiProvider()->respondWith(new AiReply('Bonjour !', [], 0.9));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $sent = $this->postJson('/api/v1/public/widget/messages', [
            'token' => $token,
            'body' => 'Bonjour, une question générale.',
        ])->assertCreated();

        $this->actingInWorkspace($admin, $workspace)
            ->getJson("/api/v1/workspace/conversations/{$sent->json('data.conversation_id')}")
            ->assertOk()
            ->assertJsonPath('data.assigned_user', null);
    }
}
