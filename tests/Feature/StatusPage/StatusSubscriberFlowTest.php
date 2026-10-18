<?php

namespace Tests\Feature\StatusPage;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\StatusPage\Support\StatusSubscriberToken;
use App\Models\StatusSubscriber;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusSubscriberFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function s_inscrire_deux_fois_avec_la_meme_adresse_ne_cree_pas_de_doublon(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/v1/public/status/{$workspace->id}/subscribe", ['email' => 'client@exemple.fr'])->assertStatus(204);
        $this->postJson("/api/v1/public/status/{$workspace->id}/subscribe", ['email' => 'client@exemple.fr'])->assertStatus(204);

        $this->assertSame(1, StatusSubscriber::query()->where('workspace_id', $workspace->id)->count());
    }

    #[Test]
    public function un_administrateur_consulte_et_retire_un_abonne(): void
    {
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $subscriber = StatusSubscriber::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($admin, $workspace)
            ->getJson('/api/v1/workspace/status-page/subscribers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $subscriber->email);

        $this->actingInWorkspace($admin, $workspace)
            ->deleteJson("/api/v1/workspace/status-page/subscribers/{$subscriber->id}")
            ->assertStatus(204);

        $this->assertSame(0, StatusSubscriber::query()->count());
    }

    #[Test]
    public function un_lien_de_desinscription_valide_retire_l_abonne(): void
    {
        $subscriber = StatusSubscriber::factory()->create();
        $token = app(StatusSubscriberToken::class)->issue($subscriber->workspace_id, $subscriber->id);

        $this->postJson('/api/v1/public/status/unsubscribe', ['token' => $token])->assertStatus(204);

        $this->assertSame(0, StatusSubscriber::query()->where('id', $subscriber->id)->count());
    }

    #[Test]
    public function un_jeton_de_desinscription_invalide_repond_204_sans_rien_supprimer(): void
    {
        $subscriber = StatusSubscriber::factory()->create();

        $this->postJson('/api/v1/public/status/unsubscribe', ['token' => 'faux-jeton'])->assertStatus(204);

        $this->assertSame(1, StatusSubscriber::query()->where('id', $subscriber->id)->count());
    }
}
