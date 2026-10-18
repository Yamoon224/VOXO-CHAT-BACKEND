<?php

namespace Tests\Feature\StatusPage;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\StatusMaintenance;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusMaintenanceManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_planifie_une_maintenance(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/status-page/maintenances', [
                'title' => 'Mise à jour de la base de données',
                'scheduled_start' => now()->addDay()->toIso8601String(),
                'scheduled_end' => now()->addDay()->addHours(2)->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Mise à jour de la base de données')
            ->assertJsonPath('data.status', 'scheduled');
    }

    #[Test]
    public function la_fin_doit_suivre_le_debut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->postJson('/api/v1/workspace/status-page/maintenances', [
                'title' => 'Maintenance',
                'scheduled_start' => now()->addDay()->toIso8601String(),
                'scheduled_end' => now()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function modifier_et_supprimer_une_maintenance(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $maintenance = StatusMaintenance::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/status-page/maintenances/{$maintenance->id}", [
                'title' => 'Maintenance reportée',
                'status' => 'scheduled',
                'scheduled_start' => now()->addDays(2)->toIso8601String(),
                'scheduled_end' => now()->addDays(2)->addHours(1)->toIso8601String(),
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Maintenance reportée');

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/status-page/maintenances/{$maintenance->id}")
            ->assertStatus(204);
    }
}
