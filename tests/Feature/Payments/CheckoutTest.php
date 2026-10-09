<?php

namespace Tests\Feature\Payments;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_proprietaire_demarre_le_paiement_d_un_palier(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $plan->update(['provider_price_id' => 'price_123']);

        $response = $this->actingInWorkspace($owner, $workspace)
            ->postJson('/api/v1/workspace/billing/checkout', ['plan_slug' => 'business'])
            ->assertOk();

        $this->assertStringStartsWith('https://checkout.stripe.test/', $response->json('checkout_url'));
        $this->assertCount(1, $this->paymentGateway()->checkoutSessions());
    }

    #[Test]
    public function l_offre_sur_devis_ne_se_souscrit_pas_en_self_service(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::where('slug', 'enterprise')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($owner, $workspace)
            ->postJson('/api/v1/workspace/billing/checkout', ['plan_slug' => 'enterprise'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'plan_requires_sales_contact');
    }

    #[Test]
    public function un_agent_ne_peut_pas_demarrer_de_paiement(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $plan = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/billing/checkout', ['plan_slug' => 'business'])
            ->assertStatus(403);
    }
}
