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
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    #[Test]
    public function une_reponse_publique_leve_le_drapeau_d_escalade(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'needs_human' => true]);
        $conversations = new InMemoryConversationRepository($conversation);
        $service = new MessageService($conversations, new InMemoryMessageRepository);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $service->postAgentMessage($scope, $conversation->id, 'Je peux vous aider.', MessageVisibility::Public);

        $this->assertFalse($conversation->needs_human);
    }

    #[Test]
    public function une_note_interne_ne_leve_pas_le_drapeau_d_escalade(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1', 'needs_human' => true]);
        $conversations = new InMemoryConversationRepository($conversation);
        $messages = new InMemoryMessageRepository;
        $service = new MessageService($conversations, $messages);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $message = $service->postAgentMessage($scope, $conversation->id, 'À vérifier avant de répondre.', MessageVisibility::Internal);

        $this->assertTrue($conversation->needs_human);
        $this->assertSame(MessageVisibility::Internal, $message->visibility);
        $this->assertSame('user-1', $message->sender_user_id);
    }
}
