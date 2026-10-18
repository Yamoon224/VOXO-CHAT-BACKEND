<?php

namespace Tests\Feature\Integrations;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Conversation;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookDispatchFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_conversation_ouverte_par_le_widget_declenche_les_abonnements_souscrits(): void
    {
        Http::fake();
        $workspace = Workspace::factory()->create();
        $subscription = WebhookSubscription::factory()->create([
            'workspace_id' => $workspace->id,
            'event_types' => ['conversation.created', 'message.created'],
        ]);

        $session = $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")->assertCreated();
        $token = $session->json('data.token');

        $this->postJson('/api/v1/public/widget/messages', ['token' => $token, 'body' => 'Bonjour.'])->assertCreated();

        Http::assertSent(fn ($request) => $request->url() === $subscription->url
            && $request->hasHeader('X-Voxo-Signature')
            && $request['event'] === 'conversation.created');

        Http::assertSent(fn ($request) => $request->url() === $subscription->url && $request['event'] === 'message.created');

        $this->assertSame(2, WebhookDelivery::query()->where('subscription_id', $subscription->id)->count());
    }

    #[Test]
    public function un_abonnement_desactive_ou_non_souscrit_ne_recoit_rien(): void
    {
        Http::fake();
        $workspace = Workspace::factory()->create();
        WebhookSubscription::factory()->create([
            'workspace_id' => $workspace->id,
            'event_types' => ['conversation.resolved'],
        ]);
        $disabled = WebhookSubscription::factory()->create([
            'workspace_id' => $workspace->id,
            'event_types' => ['conversation.created'],
            'enabled' => false,
        ]);

        $this->postJson("/api/v1/public/widget/{$workspace->id}/sessions")
            ->assertCreated();

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::query()->where('subscription_id', $disabled->id)->count());
    }

    #[Test]
    public function resoudre_une_conversation_declenche_l_abonnement_souscrit(): void
    {
        Http::fake();
        $workspace = Workspace::factory()->create();
        $admin = $this->memberOf($workspace, WorkspaceRole::Admin);
        $subscription = WebhookSubscription::factory()->create([
            'workspace_id' => $workspace->id,
            'event_types' => ['conversation.resolved'],
        ]);
        $conversation = Conversation::factory()->create(['workspace_id' => $workspace->id, 'status' => 'open']);

        $this->actingInWorkspace($admin, $workspace)
            ->putJson("/api/v1/workspace/conversations/{$conversation->id}/status", ['status' => 'resolved'])
            ->assertOk();

        Http::assertSent(fn ($request) => $request->url() === $subscription->url && $request['event'] === 'conversation.resolved');
    }
}
