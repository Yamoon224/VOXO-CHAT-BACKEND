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

    /** Délai moyen, en secondes, entre l'ouverture d'une conversation et sa première réponse (agent, IA ou système). `null` sans aucune réponse sur la période. */
    public function averageFirstResponseSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float;

    /** Délai moyen, en secondes, entre l'ouverture d'une conversation résolue et sa résolution. `null` sans aucune résolution sur la période. */
    public function averageResolutionSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float;

    /** @return list<array{user_id: string, count: int}> Messages publics envoyés par chaque agent. */
    public function countMessagesByAgent(string $workspaceId, Carbon $from, Carbon $to): array;

    /** @return list<array{channel: string, count: int}> */
    public function countConversationsByChannel(string $workspaceId, Carbon $from, Carbon $to): array;

    /**
     * Documents de la base de connaissances les plus cités par l'agent IA — un
     * indicateur des sujets fréquents (section 2.12), sans classification
     * dédiée : ce que l'agent IA cite déjà le dit.
     *
     * @return list<array{title: string, count: int}>
     */
    public function topCitedKnowledgeDocuments(string $workspaceId, Carbon $from, Carbon $to, int $limit = 5): array;

    /**
     * Questions auxquelles l'agent IA a répondu sans citer aucun passage de
     * la base de connaissances — un signal de lacune de contenu (section
     * 2.12), distinct de `unanswered` dans `countByResolutionOutcome` (qui ne
     * compte que les conversations sans réponse du tout).
     *
     * @return array{count: int, recent: list<array{question: string, created_at: Carbon}>}
     */
    public function unansweredKnowledgeQuestions(string $workspaceId, Carbon $from, Carbon $to, int $limit = 10): array;
}
