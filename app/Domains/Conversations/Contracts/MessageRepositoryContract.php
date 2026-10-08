<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\Message;
use Illuminate\Support\Collection;

interface MessageRepositoryContract
{
    /**
     * Tous les messages, notes internes comprises : vue de l'équipe.
     *
     * @return Collection<int, Message>
     */
    public function forConversation(string $conversationId): Collection;

    /**
     * Seulement les messages visibles du visiteur : son fil, et l'historique
     * donné à l'agent IA.
     *
     * @return Collection<int, Message>
     */
    public function publicForConversation(string $conversationId): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Message;

    /**
     * Réponse de l'agent IA : encapsule le type d'émetteur, pour que les
     * domaines appelants (`Assistant`) n'aient pas à connaître cet enum.
     *
     * @param  list<array{title: string, content: string, citation_url: string|null}>  $citations
     */
    public function recordAiReply(string $conversationId, string $workspaceId, string $body, array $citations): Message;

    /** Message système (ex. avis d'escalade) : même raison que `recordAiReply()`. */
    public function recordSystemNotice(string $conversationId, string $workspaceId, string $body): Message;
}
