<?php

namespace App\Domains\Conversations\Contracts;

use Illuminate\Support\Carbon;

/**
 * Lecture étroite exposée au domaine `Analytics` (section 4.4 : Analytics
 * lit, n'écrit pas) : des agrégats, jamais une conversation ni un message.
 */
interface ConversationReaderContract
{
    public function countConversations(string $workspaceId, Carbon $from, Carbon $to): int;

    public function countMessages(string $workspaceId, Carbon $from, Carbon $to): int;

    /** Note moyenne (1 à 5) des conversations évaluées par leur visiteur, ou `null` sans évaluation. */
    public function averageRating(string $workspaceId, Carbon $from, Carbon $to): ?float;

    /**
     * Répartition des conversations par issue : résolues par l'IA seule (jamais
     * escaladées), escaladées à un humain, ou restées sans aucune réponse.
     *
     * @return array{ai_resolved: int, escalated: int, unanswered: int}
     */
    public function countByResolutionOutcome(string $workspaceId, Carbon $from, Carbon $to): array;
}
