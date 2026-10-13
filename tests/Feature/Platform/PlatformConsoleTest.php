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

    #[Test]
    public function un_administrateur_de_plateforme_cree_un_palier(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);

        $this->actingInWorkspace($admin, null)
            ->postJson('/api/v1/platform/plans', [
                'slug' => 'pro', 'name' => 'Pro', 'price_cents' => 2900, 'currency' => 'EUR',
                'billing_interval' => 'month', 'max_seats' => 10, 'max_contacts' => 5000,
                'ai_credits_per_month' => 2000, 'max_knowledge_documents' => 500,
                'is_custom' => false, 'is_active' => true, 'sort_order' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'pro');

        $this->assertDatabaseHas('plans', ['slug' => 'pro']);
    }

    #[Test]
    public function la_creation_d_un_palier_refuse_un_identifiant_deja_pris(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);

        $this->actingInWorkspace($admin, null)
            ->postJson('/api/v1/platform/plans', [
                'slug' => 'free', 'name' => 'Doublon', 'currency' => 'EUR', 'billing_interval' => 'month',
                'is_custom' => false, 'is_active' => true, 'sort_order' => 9,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    #[Test]
    public function un_administrateur_de_plateforme_modifie_un_palier(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        $this->actingInWorkspace($admin, null)
            ->putJson("/api/v1/platform/plans/{$plan->id}", ['name' => 'Starter Plus', 'max_seats' => 8])
            ->assertOk()
            ->assertJsonPath('data.name', 'Starter Plus')
            ->assertJsonPath('data.max_seats', 8);
    }

    #[Test]
    public function un_administrateur_de_plateforme_supprime_un_palier_inutilise(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);
        $plan = Plan::factory()->create(['slug' => 'a-retirer']);

        $this->actingInWorkspace($admin, null)
            ->deleteJson("/api/v1/platform/plans/{$plan->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    #[Test]
    public function un_palier_rattache_a_un_abonnement_ne_se_supprime_pas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(User::PLATFORM_ADMIN_ROLE);
        $plan = Plan::where('slug', 'business')->firstOrFail();
        $workspace = Workspace::factory()->create();
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($admin, null)
            ->deleteJson("/api/v1/platform/plans/{$plan->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'plan_in_use');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    #[Test]
    public function un_compte_ordinaire_ne_peut_pas_gerer_les_paliers(): void
    {
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'free')->firstOrFail();

        $this->actingInWorkspace($user, null)
            ->putJson("/api/v1/platform/plans/{$plan->id}", ['name' => 'Piraté'])
            ->assertStatus(403);
    }
}
