<?php

namespace App\Domains\Analytics\DTOs;

use Illuminate\Support\Carbon;

final readonly class AnalyticsOverview
{
    /**
     * @param  list<array{user_id: string, count: int}>  $messagesByAgent
     * @param  list<array{channel: string, count: int}>  $conversationsByChannel
     * @param  list<array{title: string, count: int}>  $topCitedDocuments
     * @param  list<array{question: string, created_at: Carbon}>  $recentUnansweredKnowledgeQuestions
     */
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $conversationCount,
        public int $messageCount,
        public ?float $averageRating,
        public int $aiResolvedCount,
        public int $escalatedCount,
        public int $unansweredCount,
        public ?float $averageFirstResponseSeconds,
        public ?float $averageResolutionSeconds,
        public array $messagesByAgent,
        public array $conversationsByChannel,
        public array $topCitedDocuments,
        public int $unansweredKnowledgeQuestionsCount,
        public array $recentUnansweredKnowledgeQuestions,
    ) {}
}
