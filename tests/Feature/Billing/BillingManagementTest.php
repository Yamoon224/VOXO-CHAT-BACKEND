<?php

namespace Tests\Feature\Billing;

use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_proprietaire_consulte_la_grille_tarifaire(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);

        $this->actingInWorkspace($owner, $workspace)
            ->getJson('/api/v1/workspace/billing/plans')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'free');
    }

    #[Test]
    public function un_proprietaire_consulte_son_abonnement_et_son_solde_de_credits(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($owner, $workspace)
            ->getJson('/api/v1/workspace/billing/subscription')
            ->assertOk()
            ->assertJsonPath('data.plan.slug', 'business')
            ->assertJsonPath('data.ai_credit_balance', 0);
    }

    #[Test]
    public function un_viewer_n_a_pas_acces_a_la_facturation(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        $plan = Plan::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/billing/subscription')
            ->assertStatus(403);
    }

    #[Test]
    public function un_agent_ne_peut_pas_changer_de_palier(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $plan = Plan::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/billing/subscription/cancel')
            ->assertStatus(403);
    }

    #[Test]
    public function un_proprietaire_resilie_son_abonnement(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $business = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create([
            'workspace_id' => $workspace->id, 'plan_id' => $business->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->actingInWorkspace($owner, $workspace)
            ->postJson('/api/v1/workspace/billing/subscription/cancel')
            ->assertOk()
            ->assertJsonPath('data.plan.slug', 'free')
            ->assertJsonPath('data.status', 'canceled');
    }

    #[Test]
    public function un_proprietaire_consulte_ses_factures(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        Invoice::factory()->create(['workspace_id' => $workspace->id, 'subscription_id' => $subscription->id]);

        $this->actingInWorkspace($owner, $workspace)
            ->getJson('/api/v1/workspace/billing/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function les_factures_d_un_espace_n_apparaissent_pas_dans_un_autre(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();
        $ownerA = $this->memberOf($workspaceA, WorkspaceRole::Owner);
        $planA = Plan::factory()->create();
        $planB = Plan::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspaceA->id, 'plan_id' => $planA->id]);
        $subscriptionB = Subscription::factory()->create(['workspace_id' => $workspaceB->id, 'plan_id' => $planB->id]);
        Invoice::factory()->create(['workspace_id' => $workspaceB->id, 'subscription_id' => $subscriptionB->id]);

        $this->actingInWorkspace($ownerA, $workspaceA)
            ->getJson('/api/v1/workspace/billing/invoices')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
