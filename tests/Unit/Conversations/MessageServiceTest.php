<?php

namespace Tests\Unit\Conversations;

use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Conversations\Services\MessageService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryConversationRepository;
use Tests\Support\Fakes\InMemoryMessageRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\NullWebhookDispatcher;
use Tests\Support\Fakes\SpyOutboundChannelDispatcher;
use Tests\Support\Fakes\SpyWebhookDispatcher;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    #[Test]
    public function une_reponse_publique_leve_le_drapeau_d_escalade(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'needs_human' => true]);
        $conversations = new InMemoryConversationRepository($conversation);
        $webhooks = new SpyWebhookDispatcher;
        $service = new MessageService($conversations, new InMemoryMessageRepository, new SpyOutboundChannelDispatcher, $webhooks);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $service->postAgentMessage($scope, $conversation->id, 'Je peux vous aider.', MessageVisibility::Public);

        $this->assertFalse($conversation->needs_human);
        $this->assertSame(['message.created'], $webhooks->eventTypes());
    }

    #[Test]
    public function une_note_interne_ne_leve_pas_le_drapeau_d_escalade(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'needs_human' => true]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $service = new MessageService($conversations, $messages, new SpyOutboundChannelDispatcher, new NullWebhookDispatcher);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $message = $service->postAgentMessage($scope, $conversation->id, 'À vérifier avant de répondre.', MessageVisibility::Internal);

        $this->assertTrue($conversation->needs_human);
        $this->assertSame(MessageVisibility::Internal, $message->visibility);
        $this->assertSame('user-1', $message->sender_user_id);
    }

    #[Test]
    public function une_reponse_publique_est_relayee_au_canal_d_origine(): void
    {
        // `visitor_id` porte l'adresse de relais (lot 5 : e-mail, téléphone ou
        // identifiant externe selon le canal) — voir `InboundChannelService`.
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'visitor_id' => 'client@exemple.fr']);
        $conversations = new InMemoryConversationRepository($conversation);
        $dispatcher = new SpyOutboundChannelDispatcher;
        $service = new MessageService($conversations, new InMemoryMessageRepository, $dispatcher, new NullWebhookDispatcher);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $service->postAgentMessage($scope, $conversation->id, 'Voici la réponse.', MessageVisibility::Public);

        $this->assertCount(1, $dispatcher->calls());
        $this->assertSame('client@exemple.fr', $dispatcher->calls()[0]['recipientEmail']);
        $this->assertSame('Voici la réponse.', $dispatcher->calls()[0]['body']);
    }

    #[Test]
    public function une_note_interne_n_est_jamais_relayee(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'visitor_email' => 'client@exemple.fr']);
        $conversations = new InMemoryConversationRepository($conversation);
        $dispatcher = new SpyOutboundChannelDispatcher;
        $service = new MessageService($conversations, new InMemoryMessageRepository, $dispatcher, new NullWebhookDispatcher);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $service->postAgentMessage($scope, $conversation->id, 'Note interne.', MessageVisibility::Internal);

        $this->assertSame([], $dispatcher->calls());
    }
}
