<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\Message;
use Illuminate\Support\Collection;

interface MessageRepositoryContract
{
    /** Tous les messages, notes internes comprises : vue de l'équipe. @return Collection<int, Message> */
    public function forConversation(string $conversationId): Collection;

    /** Seulement les messages visibles du visiteur : son fil, et l'historique donné à l'agent IA. @return Collection<int, Message> */
    public function publicForConversation(string $conversationId): Collection;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Message;
}
