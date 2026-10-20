<?php

namespace Tests\Unit\Realtime;

use App\Domains\Realtime\Support\ConversationChannelAuthorizer;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Widget\Support\VisitorPrincipal;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationChannelAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_membre_de_l_espace_accede_au_canal_prive(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $result = app(ConversationChannelAuthorizer::class)->authorizeAgentChannel($agent, $conversation->id);

        $this->assertSame(['id' => $agent->id, 'name' => $agent->name], $result);
    }

    #[Test]
    public function un_agent_etranger_a_l_espace_n_accede_pas_au_canal_prive(): void
    {
        $workspace = Workspace::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $stranger = $this->memberOf($otherWorkspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $result = app(ConversationChannelAuthorizer::class)->authorizeAgentChannel($stranger, $conversation->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function un_visiteur_n_accede_jamais_au_canal_prive(): void
    {
        $workspace = Workspace::factory()->create();
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'visitor_id' => 'visitor-1']);
        $visitor = new VisitorPrincipal($workspace->id, 'visitor-1');

        $result = app(ConversationChannelAuthorizer::class)->authorizeAgentChannel($visitor, $conversation->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function une_conversation_inconnue_refuse_le_canal_prive(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $result = app(ConversationChannelAuthorizer::class)->authorizeAgentChannel($agent, (string) Str::uuid());

        $this->assertFalse($result);
    }

    #[Test]
    public function le_visiteur_proprietaire_accede_au_canal_public(): void
    {
        $workspace = Workspace::factory()->create();
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'visitor_id' => 'visitor-1']);
        $visitor = new VisitorPrincipal($workspace->id, 'visitor-1');

        $result = app(ConversationChannelAuthorizer::class)->authorizePublicChannel($visitor, $conversation->id);

        $this->assertSame(['id' => 'visitor:visitor-1', 'name' => 'Visiteur'], $result);
    }

    #[Test]
    public function un_visiteur_etranger_n_accede_pas_au_canal_public_d_un_autre_visiteur(): void
    {
        $workspace = Workspace::factory()->create();
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'visitor_id' => 'visitor-1']);
        $stranger = new VisitorPrincipal($workspace->id, 'visitor-2');

        $result = app(ConversationChannelAuthorizer::class)->authorizePublicChannel($stranger, $conversation->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function un_agent_membre_de_l_espace_accede_aussi_au_canal_public(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);

        $result = app(ConversationChannelAuthorizer::class)->authorizePublicChannel($agent, $conversation->id);

        $this->assertSame(['id' => $agent->id, 'name' => $agent->name], $result);
    }

    #[Test]
    public function un_utilisateur_non_membre_refuse_le_canal_public(): void
    {
        $workspace = Workspace::factory()->create();
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id]);
        $stranger = User::factory()->create();

        $result = app(ConversationChannelAuthorizer::class)->authorizePublicChannel($stranger, $conversation->id);

        $this->assertFalse($result);
    }
}
