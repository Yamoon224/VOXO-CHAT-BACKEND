<?php

namespace Tests\Feature\Analytics;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsAdvancedMetricsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_delais_de_premiere_reponse_et_de_resolution_sont_moyennes(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $opened = Carbon::parse('2026-10-01 10:00:00');
        $conversation = Conversation::factory()->create([
            'workspace_id' => $workspace->id,
            'created_at' => $opened,
            'status' => 'resolved',
            'resolved_at' => $opened->copy()->addMinutes(20),
        ]);
        Message::factory()->fromAgent($agent->id)->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'created_at' => $opened->copy()->addMinutes(2),
        ]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview?from=2026-09-01&to=2026-11-01')
            ->assertOk();

        $this->assertEquals(120.0, $response->json('data.average_first_response_seconds'));
        $this->assertEquals(1200.0, $response->json('data.average_resolution_seconds'));
    }

    #[Test]
    public function l_activite_se_repartit_par_agent_et_par_canal(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversationWidget = Conversation::factory()->create(['workspace_id' => $workspace->id, 'channel' => 'widget']);
        $conversationEmail = Conversation::factory()->create(['workspace_id' => $workspace->id, 'channel' => 'email']);
        Message::factory()->fromAgent($agent->id)->count(2)->create(['conversation_id' => $conversationWidget->id, 'workspace_id' => $workspace->id]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk();

        $response->assertJsonFragment(['user_id' => $agent->id, 'count' => 2]);
        $this->assertEqualsCanonicalizing(
            [['channel' => 'widget', 'count' => 1], ['channel' => 'email', 'count' => 1]],
            $response->json('data.conversations_by_channel'),
        );
    }

    #[Test]
    public function les_documents_les_plus_cites_sont_classes_par_frequence(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);
        Message::factory()->fromAi()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'citations' => [['title' => 'Politique de remboursement', 'content' => '...', 'citation_url' => null]],
        ]);
        Message::factory()->fromAi()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'citations' => [['title' => 'Politique de remboursement', 'content' => '...', 'citation_url' => null]],
        ]);
        Message::factory()->fromAi()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'citations' => [['title' => 'Horaires d\'ouverture', 'content' => '...', 'citation_url' => null]],
        ]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk();

        $this->assertSame(
            [['title' => 'Politique de remboursement', 'count' => 2], ['title' => "Horaires d'ouverture", 'count' => 1]],
            $response->json('data.top_cited_documents'),
        );
    }

    #[Test]
    public function une_reponse_ia_sans_citation_est_une_question_sans_reponse_de_la_base(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'body' => 'Faites-vous de la livraison sur Mars ?',
            'created_at' => now()->subMinute(),
        ]);
        Message::factory()->fromAi()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'citations' => [],
        ]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk();

        $this->assertSame(1, $response->json('data.unanswered_knowledge_questions_count'));
        $this->assertSame('Faites-vous de la livraison sur Mars ?', $response->json('data.recent_unanswered_knowledge_questions.0.question'));
    }

    #[Test]
    public function une_reponse_ia_avec_citation_n_est_pas_comptee_comme_sans_reponse(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);
        Message::factory()->fromAi()->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $workspace->id,
            'citations' => [['title' => 'FAQ', 'content' => '...', 'citation_url' => null]],
        ]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->getJson('/api/v1/workspace/analytics/overview')
            ->assertOk();

        $this->assertSame(0, $response->json('data.unanswered_knowledge_questions_count'));
    }

    #[Test]
    public function exporter_renvoie_un_csv_telechargeable(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $response = $this->actingInWorkspace($agent, $workspace)->get('/api/v1/workspace/analytics/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('summary,conversation_count,1', $response->getContent());
    }
}
