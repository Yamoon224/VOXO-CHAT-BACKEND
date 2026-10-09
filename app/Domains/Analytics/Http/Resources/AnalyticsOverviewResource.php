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
        ];
    }
}
