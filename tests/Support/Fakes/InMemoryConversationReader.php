<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\ConversationReaderContract;
use Illuminate\Support\Carbon;

final class InMemoryConversationReader implements ConversationReaderContract
{
    /**
     * @param  list<array{user_id: string, count: int}>  $messagesByAgent
     * @param  list<array{channel: string, count: int}>  $conversationsByChannel
     * @param  list<array{title: string, count: int}>  $topCitedDocuments
     * @param  list<array{question: string, created_at: Carbon}>  $recentUnansweredKnowledgeQuestions
     */
    public function __construct(
        private readonly int $conversationCount = 0,
        private readonly int $messageCount = 0,
        private readonly ?float $averageRating = null,
        private readonly int $aiResolved = 0,
        private readonly int $escalated = 0,
        private readonly int $unanswered = 0,
        private readonly ?float $averageFirstResponseSeconds = null,
        private readonly ?float $averageResolutionSeconds = null,
        private readonly array $messagesByAgent = [],
        private readonly array $conversationsByChannel = [],
        private readonly array $topCitedDocuments = [],
        private readonly int $unansweredKnowledgeQuestionsCount = 0,
        private readonly array $recentUnansweredKnowledgeQuestions = [],
    ) {}

    public function countConversations(string $workspaceId, Carbon $from, Carbon $to): int
    {
        return $this->conversationCount;
    }

    public function countMessages(string $workspaceId, Carbon $from, Carbon $to): int
    {
        return $this->messageCount;
    }

    public function averageRating(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        return $this->averageRating;
    }

    /** @return array{ai_resolved: int, escalated: int, unanswered: int} */
    public function countByResolutionOutcome(string $workspaceId, Carbon $from, Carbon $to): array
    {
        return ['ai_resolved' => $this->aiResolved, 'escalated' => $this->escalated, 'unanswered' => $this->unanswered];
    }

    public function averageFirstResponseSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        return $this->averageFirstResponseSeconds;
    }

    public function averageResolutionSeconds(string $workspaceId, Carbon $from, Carbon $to): ?float
    {
        return $this->averageResolutionSeconds;
    }

    public function countMessagesByAgent(string $workspaceId, Carbon $from, Carbon $to): array
    {
        return $this->messagesByAgent;
    }

    public function countConversationsByChannel(string $workspaceId, Carbon $from, Carbon $to): array
    {
        return $this->conversationsByChannel;
    }

    public function topCitedKnowledgeDocuments(string $workspaceId, Carbon $from, Carbon $to, int $limit = 5): array
    {
        return $this->topCitedDocuments;
    }

    public function unansweredKnowledgeQuestions(string $workspaceId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        return ['count' => $this->unansweredKnowledgeQuestionsCount, 'recent' => $this->recentUnansweredKnowledgeQuestions];
    }
}
