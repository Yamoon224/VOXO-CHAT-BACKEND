<?php

namespace Tests\Feature\Channels;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelConnectionManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_connexion_non_configuree_est_desactivee_par_defaut(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/telegram/connection')
            ->assertOk()
            ->assertJsonPath('data.channel', 'telegram')
            ->assertJsonPath('data.enabled', false);
    }

    #[Test]
    public function activer_un_canal_sans_ses_identifiants_echoue(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', ['enabled' => true])
            ->assertStatus(422);
    }

    #[Test]
    public function enregistrer_les_identifiants_puis_activer_reussit(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', [
                'credentials' => ['bot_token' => '123456:abcdefghijklmnop'],
            ])->assertOk();

        $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);
    }

    #[Test]
    public function les_identifiants_relus_sont_masques(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $response = $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/telegram/connection', [
                'credentials' => ['bot_token' => '123456:abcdefghijklmnop'],
            ])->assertOk();

        $masked = $response->json('data.credentials.bot_token');
        $this->assertStringEndsWith('mnop', $masked);
        $this->assertStringNotContainsString('123456:abcdefgh', $masked);
    }

    #[Test]
    public function le_jeton_de_verification_meta_n_est_jamais_masque(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $first = $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/whatsapp/connection')
            ->assertOk();

        $verifyToken = $first->json('data.credentials.verify_token');
        $this->assertNotEmpty($verifyToken);

        // Une seconde requête (enregistrement du jeton d'accès) ne doit pas
        // écraser le jeton de vérification déjà engendré à la création.
        $second = $this->actingInWorkspace($admin, $workspace)
            ->putJson('/api/v1/workspace/channels/whatsapp/connection', [
                'credentials' => ['access_token' => 'token-abc', 'phone_number_id' => '1234567890'],
            ])->assertOk();

        $this->assertSame($verifyToken, $second->json('data.credentials.verify_token'));
    }

    #[Test]
    public function un_agent_ne_peut_pas_modifier_une_connexion(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/channels/telegram/connection')
            ->assertStatus(403);
    }

    #[Test]
    public function un_canal_inconnu_dans_l_url_est_rejete(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/channels/carrier-pigeon/connection')
            ->assertStatus(404);
    }
}
