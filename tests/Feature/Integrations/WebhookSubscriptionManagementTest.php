<?php

namespace Tests\Feature\Integrations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\WebhookSubscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_cree_un_abonnement_webhook(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/integrations/webhooks', [
                'url' => 'https://exemple.fr/webhooks/voxo',
                'event_types' => ['conversation.created', 'message.created'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://exemple.fr/webhooks/voxo')
            ->assertJsonPath('data.enabled', true);

        $this->assertNotEmpty($response->json('data.secret'));
    }

    #[Test]
    public function un_agent_ne_peut_pas_gerer_les_integrations(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/integrations/webhooks')
            ->assertStatus(403);
    }

    #[Test]
    public function un_type_d_evenement_inconnu_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/integrations/webhooks', [
                'url' => 'https://exemple.fr/webhooks/voxo',
                'event_types' => ['invente.inconnu'],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function modifier_et_supprimer_un_abonnement(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $subscription = WebhookSubscription::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/integrations/webhooks/{$subscription->id}", [
                'url' => 'https://exemple.fr/autre',
                'event_types' => ['conversation.resolved'],
                'enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.url', 'https://exemple.fr/autre')
            ->assertJsonPath('data.enabled', false);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/integrations/webhooks/{$subscription->id}")
            ->assertStatus(204);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/integrations/webhooks')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function un_espace_etranger_ne_voit_pas_les_abonnements_d_un_autre(): void
    {
        $workspace = Workspace::factory()->create();
        $other = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $foreignSubscription = WebhookSubscription::factory()->create(['workspace_id' => $other->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/integrations/webhooks/{$foreignSubscription->id}", [
                'url' => 'https://exemple.fr/autre',
                'event_types' => ['conversation.created'],
            ])
            ->assertStatus(404);
    }
}
