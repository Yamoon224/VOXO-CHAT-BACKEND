<?php

namespace App\Domains\Analytics\Http\Resources;

use App\Domains\Analytics\DTOs\AnalyticsOverview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnalyticsOverview */
class AnalyticsOverviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'from' => $this->from->toIso8601String(),
            'to' => $this->to->toIso8601String(),
            'conversation_count' => $this->conversationCount,
            'message_count' => $this->messageCount,
            'average_rating' => $this->averageRating,
            'ai_resolved_count' => $this->aiResolvedCount,
            'escalated_count' => $this->escalatedCount,
            'unanswered_count' => $this->unansweredCount,
            'average_first_response_seconds' => $this->averageFirstResponseSeconds,
            'average_resolution_seconds' => $this->averageResolutionSeconds,
            'messages_by_agent' => $this->messagesByAgent,
            'conversations_by_channel' => $this->conversationsByChannel,
            'top_cited_documents' => $this->topCitedDocuments,
            'unanswered_knowledge_questions_count' => $this->unansweredKnowledgeQuestionsCount,
            'recent_unanswered_knowledge_questions' => array_map(
                fn (array $row) => ['question' => $row['question'], 'created_at' => $row['created_at']->toIso8601String()],
                $this->recentUnansweredKnowledgeQuestions,
            ),
        ];
    }
}
