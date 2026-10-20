<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Assistant\Contracts\AssistantResponderContract;
use App\Domains\Automations\Contracts\AutomationResponderContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Conversations\Exceptions\AssigneeNotMemberException;
use App\Domains\Integrations\Contracts\WebhookDispatcherContract;
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
        private readonly RoutingExecutionService $routing,
        private readonly AutomationResponderContract $automations,
        private readonly WebhookDispatcherContract $webhooks,
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

        $conversation = $this->conversations->update($conversation, ['assigned_user_id' => $userId]);

        // `findInWorkspaceOrFail` a chargé `assignedUser` avant l'affectation :
        // la relation déjà en mémoire ne se met pas à jour toute seule quand
        // seule la colonne `assigned_user_id` change.
        return $conversation->load('assignedUser');
    }

    public function changeStatus(WorkspaceScope $scope, string $conversationId, ConversationStatus $status): Conversation
    {
        $conversation = $this->conversations->findInWorkspaceOrFail($scope->workspaceId, $conversationId);

        $conversation = $this->conversations->update($conversation, [
            'status' => $status,
            'resolved_at' => $status === ConversationStatus::Resolved ? now() : null,
        ]);

        if ($status === ConversationStatus::Resolved) {
            $this->webhooks->dispatch($scope->workspaceId, 'conversation.resolved', [
                'id' => $conversation->id,
                'channel' => $conversation->channel->value,
                'contact_id' => $conversation->contact_id,
                'resolved_at' => $conversation->resolved_at,
            ]);
        }

        return $conversation;
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
        return $this->receiveChannelMessage($workspaceId, ConversationChannel::Widget->value, $visitorId, $body, $visitorName, $visitorEmail);
    }

    /** Même mécanique, pour un canal autre que le widget (`Channels`) — voir `VisitorConversationContract`. */
    public function receiveChannelMessage(
        string $workspaceId,
        string $channel,
        string $visitorId,
        string $body,
        ?string $visitorName = null,
        ?string $visitorEmail = null,
        ?string $contactId = null,
        ?string $subject = null,
    ): Conversation {
        $automationHandled = false;
        $isNewConversation = false;
        $message = null;

        $conversation = $this->transactions->run(
            function () use ($workspaceId, $channel, $visitorId, $body, $visitorName, $visitorEmail, $contactId, $subject, &$automationHandled, &$isNewConversation, &$message) {
                $conversation = $this->conversations->findOpenForVisitor($workspaceId, $visitorId)
                    ?? $this->conversations->create([
                        'workspace_id' => $workspaceId,
                        'channel' => ConversationChannel::from($channel),
                        'status' => ConversationStatus::Open,
                        'visitor_id' => $visitorId,
                        'contact_id' => $contactId,
                        'subject' => $subject,
                    ]);
                $isNewConversation = $conversation->wasRecentlyCreated;

                // Le routage (section 2.4) ne joue qu'à l'ouverture : un message
                // suivant sur une conversation déjà affectée ne doit pas la
                // réaffecter.
                if ($isNewConversation) {
                    $this->routing->applyTo($conversation, $body);
                }

                $attributes = array_filter([
                    'visitor_name' => $visitorName,
                    'visitor_email' => $visitorEmail,
                    'contact_id' => $contactId ?? $conversation->contact_id,
                ], fn (?string $value) => $value !== null);

                if ($attributes !== []) {
                    $conversation = $this->conversations->update($conversation, $attributes);
                }

                $message = $this->messages->create([
                    'conversation_id' => $conversation->id,
                    'workspace_id' => $workspaceId,
                    'sender_type' => MessageSenderType::Visitor,
                    // Un visiteur ne crée jamais de note interne — explicite
                    // ici, pas seulement le défaut de colonne : un modèle
                    // fraîchement créé ne le reflète pas en mémoire PHP, et
                    // `MessageBroadcastEvent::broadcastOn()` lit `isPublic()`
                    // juste après cette création (section 4.1).
                    'visibility' => MessageVisibility::Public,
                    'body' => $body,
                ]);

                $conversation = $this->conversations->update($conversation, ['last_message_at' => now()]);

                // Un scénario d'automatisation (section 2.8) qui prend la
                // main sur ce message remplace la réponse de l'agent IA pour
                // ce tour-là — jamais les deux à la fois.
                $automationHandled = $this->automations->applyTo($conversation, $body, $isNewConversation);

                if (! $automationHandled) {
                    return $conversation;
                }

                // Une étape d'automatisation a pu changer `contact_id` (ou
                // affecter un agent) sur la ligne en base sans que cette
                // copie en mémoire ne le sache — même piège que documenté
                // pour `ConversationService::assign()` : on la recharge.
                return $this->conversations->findInWorkspaceOrFail($workspaceId, $conversation->id);
            },
        );

        if ($isNewConversation) {
            $this->webhooks->dispatch($workspaceId, 'conversation.created', [
                'id' => $conversation->id,
                'channel' => $conversation->channel->value,
                'visitor_id' => $conversation->visitor_id,
                'contact_id' => $conversation->contact_id,
                'subject' => $conversation->subject,
            ]);
        }

        if ($message !== null) {
            $this->webhooks->dispatch($workspaceId, 'message.created', [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_type' => $message->sender_type->value,
                'body' => $message->body,
            ]);
        }

        if (! $automationHandled) {
            $this->assistant->respondToConversation($conversation->id);
        }

        return $conversation;
    }

    public function receiveReplyOnKnownConversation(string $workspaceId, string $conversationId, string $body): Conversation
    {
        $message = null;

        $conversation = $this->transactions->run(function () use ($workspaceId, $conversationId, $body, &$message) {
            $conversation = $this->conversations->findInWorkspaceOrFail($workspaceId, $conversationId);

            $message = $this->messages->create([
                'conversation_id' => $conversation->id,
                'workspace_id' => $workspaceId,
                'sender_type' => MessageSenderType::Visitor,
                'visibility' => MessageVisibility::Public,
                'body' => $body,
            ]);

            $reopening = $conversation->status === ConversationStatus::Resolved;

            return $this->conversations->update($conversation, [
                'last_message_at' => now(),
                'status' => $reopening ? ConversationStatus::Open : $conversation->status,
                'resolved_at' => $reopening ? null : $conversation->resolved_at,
            ]);
        });

        $this->webhooks->dispatch($workspaceId, 'message.created', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'sender_type' => $message->sender_type->value,
            'body' => $message->body,
        ]);

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
