<?php

namespace App\Domains\Realtime\Support;

use App\Domains\Realtime\Contracts\VisitorIdentity;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\Conversation;
use App\Models\User;

/**
 * Logique d'autorisation des deux canaux temps réel par conversation
 * (section 4.1), extraite de `routes/channels.php` pour être testable sans
 * passer par le protocole Pusher/Reverb complet (HMAC, `socket_id`...).
 */
final class ConversationChannelAuthorizer
{
    public function __construct(private readonly MembershipReaderContract $memberships) {}

    /**
     * `conversation.{id}` — réservé aux agents membres de l'espace de la
     * conversation : tous les messages, notes internes comprises.
     *
     * @return array{id: string, name: string}|false
     */
    public function authorizeAgentChannel(mixed $user, string $conversationId): array|false
    {
        if (! $user instanceof User) {
            return false;
        }

        $conversation = Conversation::query()->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        return $this->asAgent($user, $conversation);
    }

    /**
     * `conversation.{id}.public` — ouvert au visiteur propriétaire de la
     * conversation ET aux agents membres de l'espace : messages publics
     * seulement, présence, indicateurs de saisie.
     *
     * @return array{id: string, name: string}|false
     */
    public function authorizePublicChannel(mixed $user, string $conversationId): array|false
    {
        $conversation = Conversation::query()->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        if ($user instanceof User) {
            return $this->asAgent($user, $conversation);
        }

        if ($user instanceof VisitorIdentity) {
            return $user->workspaceId() === $conversation->workspace_id && $user->visitorId() === $conversation->visitor_id
                ? ['id' => "visitor:{$user->visitorId()}", 'name' => 'Visiteur']
                : false;
        }

        return false;
    }

    /** @return array{id: string, name: string}|false */
    private function asAgent(User $user, Conversation $conversation): array|false
    {
        $member = $this->memberships->findForUser($conversation->workspace_id, $user->id);

        return $member !== null ? ['id' => $user->id, 'name' => $user->name] : false;
    }
}
