<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Channels\Contracts\OutboundChannelDispatcherContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Integrations\Contracts\WebhookDispatcherContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\Message;
use Illuminate\Support\Collection;

final class MessageService
{
    public function __construct(
        private readonly ConversationRepositoryContract $conversations,
        private readonly MessageRepositoryContract $messages,
        private readonly OutboundChannelDispatcherContract $dispatcher,
        private readonly WebhookDispatcherContract $webhooks,
    ) {}

    /** @return Collection<int, Message> */
    public function listForAgent(WorkspaceScope $scope, string $conversationId): Collection
    {
        $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        return $this->messages->forConversation($conversationId);
    }

    public function postAgentMessage(
        WorkspaceScope $scope,
        string $conversationId,
        string $body,
        MessageVisibility $visibility,
    ): Message {
        $conversation = $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        $message = $this->messages->create([
            'conversation_id' => $conversation->id,
            'workspace_id' => $scope->workspaceId,
            'sender_type' => MessageSenderType::Agent,
            'sender_user_id' => $scope->userId,
            'visibility' => $visibility,
            'body' => $body,
        ]);

        // Un agent qui répond au visiteur a pris la relève : l'escalade n'a
        // plus lieu d'être affichée comme en attente d'un humain.
        $this->conversations->update($conversation, [
            'last_message_at' => now(),
            'needs_human' => $visibility === MessageVisibility::Public ? false : $conversation->needs_human,
        ]);

        // Note interne : jamais renvoyée au visiteur, donc rien à relayer au
        // canal d'origine (`Channels` n'en sait rien et ne doit pas en savoir).
        if ($visibility === MessageVisibility::Public) {
            $this->dispatcher->dispatchReply($scope->workspaceId, $conversation->channel->value, $conversation->visitor_id, $body);
        }

        $this->webhooks->dispatch($scope->workspaceId, 'message.created', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'sender_type' => $message->sender_type->value,
            'body' => $message->body,
        ]);

        return $message;
    }
}
