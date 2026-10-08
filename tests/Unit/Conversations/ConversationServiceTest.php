<?php

namespace Tests\Unit\Conversations;

use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Conversations\Exceptions\AssigneeNotMemberException;
use App\Domains\Conversations\Services\ConversationService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\ImmediateTransactionManager;
use Tests\Support\Fakes\InMemoryConversationRepository;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryMessageRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\SpyAssistantResponder;
use Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    #[Test]
    public function un_message_de_visiteur_ouvre_une_conversation_et_declenche_l_agent(): void
    {
        $conversations = new InMemoryConversationRepository;
        $messages = new InMemoryMessageRepository;
        $assistant = new SpyAssistantResponder;

        $service = new ConversationService(
            $conversations,
            $messages,
            new InMemoryMembershipRepository,
            $assistant,
            new ImmediateTransactionManager,
        );

        $conversation = $service->receiveVisitorMessage('workspace-1', 'visitor-1', 'Bonjour, avez-vous des horaires ?');

        $this->assertSame('workspace-1', $conversation->workspace_id);
        $this->assertCount(1, $messages->forConversation($conversation->id));
        $this->assertSame([$conversation->id], $assistant->calls());
    }

    #[Test]
    public function un_second_message_du_meme_visiteur_reutilise_la_conversation_ouverte(): void
    {
        $conversations = new InMemoryConversationRepository;
        $messages = new InMemoryMessageRepository;

        $service = new ConversationService($conversations, $messages, new InMemoryMembershipRepository, new SpyAssistantResponder, new ImmediateTransactionManager);

        $first = $service->receiveVisitorMessage('workspace-1', 'visitor-1', 'Premier message.');
        $second = $service->receiveVisitorMessage('workspace-1', 'visitor-1', 'Deuxième message.');

        $this->assertSame($first->id, $second->id);
        $this->assertCount(2, $messages->forConversation($first->id));
    }

    #[Test]
    public function affecter_a_quelqu_un_qui_n_est_pas_membre_est_refuse(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $service = new ConversationService($conversations, new InMemoryMessageRepository, new InMemoryMembershipRepository, new SpyAssistantResponder, new ImmediateTransactionManager);

        $this->expectException(AssigneeNotMemberException::class);

        $service->assign(new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin), $conversation->id, 'etranger');
    }

    #[Test]
    public function changer_le_statut_vers_resolu_horodate_la_resolution(): void
    {
        $conversation = ModelFactory::conversation(['workspace_id' => 'workspace-1']);
        $conversations = new InMemoryConversationRepository($conversation);
        $service = new ConversationService($conversations, new InMemoryMessageRepository, new InMemoryMembershipRepository, new SpyAssistantResponder, new ImmediateTransactionManager);

        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
        $resolved = $service->changeStatus($scope, $conversation->id, ConversationStatus::Resolved);

        $this->assertSame(ConversationStatus::Resolved, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }
}
