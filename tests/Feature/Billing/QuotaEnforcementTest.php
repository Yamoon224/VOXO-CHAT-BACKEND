<?php

namespace Tests\Feature\Billing;

use App\Domains\Assistant\DTOs\AiReply;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Le palier d'un espace borne ses places, ses documents indexables et ses réponses automatisées (section 2.13). */
class QuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function inviter_au_dela_du_plafond_de_places_du_palier_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = $this->memberOf($workspace, WorkspaceRole::Owner);
        $plan = Plan::factory()->create(['max_seats' => 1]);
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($owner, $workspace)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'nouveau@example.test', 'role' => 'agent'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'seat_quota_exceeded');
    }

    #[Test]
    public function importer_au_dela_du_plafond_de_documents_du_palier_est_refuse(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $plan = Plan::factory()->create(['max_knowledge_documents' => 0]);
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->actingInWorkspace($admin, $workspace)->postJson('/api/v1/workspace/knowledge/uploads', [
            'name' => 'Import',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'Contenu.')],
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'document_quota_exceeded');
    }

    #[Test]
    public function un_palier_sans_credits_ia_restants_escalade_sans_appeler_le_fournisseur_dia(): void
    {
        $workspace = Workspace::factory()->create();
        $plan = Plan::factory()->create(['ai_credits_per_month' => 10]);
        Subscription::factory()->create(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

        $this->aiProvider()->respondWith(new AiReply('Ne devrait jamais être renvoyé.', [], 0.95));

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Quels sont vos horaires ?'])
            ->assertCreated();

        $messages = $this->getJson('/api/v1/public/widget/messages?token='.urlencode($token))->assertOk();

        $this->assertCount(2, $messages->json('data'));
        $this->assertSame('system', $messages->json('data.1.sender_type'));
        $this->assertSame([], $this->aiProvider()->calls());
    }
}
