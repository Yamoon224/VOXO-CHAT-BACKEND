<?php

namespace Tests\Feature\Billing;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuotaAlertCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_tache_planifiee_alerte_un_espace_proche_de_son_plafond_de_places(): void
    {
        $plan = Plan::factory()->create(['max_seats' => 2, 'max_knowledge_documents' => null, 'ai_credits_per_month' => null]);
        $workspace = Workspace::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $owner = User::factory()->create();
        $this->memberOf($workspace, WorkspaceRole::Owner, $owner);
        $this->memberOf($workspace, WorkspaceRole::Agent);
        // 2 membres / 2 sièges = 100 %.

        $this->artisan('billing:send-quota-alerts')->assertSuccessful();

        $this->assertDatabaseHas('quota_alerts', [
            'workspace_id' => $workspace->id,
            'quota_type' => 'seats',
            'threshold' => 100,
        ]);
    }

    #[Test]
    public function un_espace_loin_de_ses_plafonds_ne_recoit_aucune_alerte(): void
    {
        $plan = Plan::factory()->create(['max_seats' => 100, 'max_knowledge_documents' => 100, 'ai_credits_per_month' => null]);
        $workspace = Workspace::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $this->memberOf($workspace, WorkspaceRole::Owner);

        $this->artisan('billing:send-quota-alerts')->assertSuccessful();

        $this->assertDatabaseMissing('quota_alerts', ['workspace_id' => $workspace->id]);
    }
}
