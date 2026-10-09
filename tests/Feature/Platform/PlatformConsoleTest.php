<?php

namespace Tests\Feature\Platform;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformConsoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_de_plateforme_liste_les_espaces_avec_leur_palier(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);

        $workspace = Workspace::factory()->create();
        $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::where('slug', 'business')->firstOrFail();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($admin, null)
            ->getJson('/api/v1/platform/workspaces')
            ->assertOk()
            ->assertJsonFragment(['id' => $workspace->id, 'plan_slug' => 'business', 'member_count' => 1]);
    }

    #[Test]
    public function un_compte_ordinaire_n_a_pas_acces_a_la_console_plateforme(): void
    {
        $user = User::factory()->create();

        $this->actingInWorkspace($user, null)
            ->getJson('/api/v1/platform/workspaces')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');
    }

    #[Test]
    public function un_invite_n_a_pas_acces_a_la_console_plateforme(): void
    {
        $this->asGuest()->getJson('/api/v1/platform/workspaces')->assertStatus(401);
    }

    #[Test]
    public function un_administrateur_de_plateforme_consulte_la_grille_tarifaire(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);

        $this->actingInWorkspace($admin, null)
            ->getJson('/api/v1/platform/plans')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'free');
    }
}
