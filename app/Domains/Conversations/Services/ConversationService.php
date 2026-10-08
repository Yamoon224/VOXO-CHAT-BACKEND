<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Assistant\Contracts\AssistantResponderContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Exceptions\AssigneeNotMemberException;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ConversationService implements VisitorConversationContract
{
    public function __construct(
        private readonly ConversationRepositoryContract $conversations,
        private readonly MessageRepositoryContract $messages,
        private readonly MembershipReaderContract $memberships,
        private readonly AssistantResponderContract $assistant,
        private readonly TransactionManagerContract $transactions,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function list(WorkspaceScope $scope, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->conversations->paginate($scope->workspaceId, $filters, $perPage);
    }

    public function get(WorkspaceScope $scope, string $conversationId): Conversation
    {
        return $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);
    }

    /** @throws AssigneeNotMemberException */
    public function assign(WorkspaceScope $scope, string $conversationId, ?string $userId): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        if ($userId !== null && $this->memberships->findForUser($scope->workspaceId, $userId) === null) {
            throw AssigneeNotMemberException::make();
        }

        return $this->conversations->update($conversation, ['assigned_user_id' => $userId]);
    }

    public function changeStatus(WorkspaceScope $scope, string $conversationId, ConversationStatus $status): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        return $this->conversations->update($conversation, [
            'status' => $status,
            'resolved_at' => $status === ConversationStatus::Resolved ? now() : null,
        ]);
    }

    /**
     * Point d'entrée du widget public : retrouve la conversation ouverte du
     * visiteur ou en ouvre une, y ajoute son message, et déclenche l'agent IA.
     */
    public function receiveVisitorMessage(
        string $workspaceId,
        string $visitorId,
        string $body,
        ?string $visitorName = null,
        ?string $visitorEmail = null,
    ): Conversation {
        $conversation = $this->transactions->run(function () use ($workspaceId, $visitorId, $body, $visitorName, $visitorEmail) {
            $conversation = $this->conversations->findOpenForVisitor($workspaceId, $visitorId)
                ?? $this->conversations->create([
                    'workspace_id' => $workspaceId,
                    'channel' => ConversationChannel::Widget,
                    'status' => ConversationStatus::Open,
                    'visitor_id' => $visitorId,
                ]);

            if ($visitorName !== null || $visitorEmail !== null) {
                $conversation = $this->conversations->update($conversation, array_filter([
                    'visitor_name' => $visitorName,
                    'visitor_email' => $visitorEmail,
                ], fn (?string $value) => $value !== null));
            }

            $this->messages->create([
                'conversation_id' => $conversation->id,
                'workspace_id' => $workspaceId,
                'sender_type' => MessageSenderType::Visitor,
                'body' => $body,
            ]);

            return $this->conversations->update($conversation, ['last_message_at' => now()]);
        });

        $this->assistant->respondToConversation($conversation->id);

        return $conversation;
    }

    public function rateByVisitor(string $workspaceId, string $conversationId, int $rating, ?string $comment): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($workspaceId, $conversationId);

        return $this->conversations->update($conversation, [
            'rating' => max(1, min(5, $rating)),
            'rating_comment' => $comment,
        ]);
    }
}
