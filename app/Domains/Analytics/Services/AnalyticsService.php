<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\DTOs\AnalyticsOverview;
use App\Domains\Conversations\Contracts\ConversationReaderContract;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Support\Carbon;

/** Lit, n'écrit pas : agrège ce que `ConversationReaderContract` expose (section 2.14). */
final class AnalyticsService
{
    public function __construct(private readonly ConversationReaderContract $conversations) {}

    public function overview(WorkspaceScope $scope, Carbon $from, Carbon $to): AnalyticsOverview
    {
        $outcomes = $this->conversations->countByResolutionOutcome($scope->workspaceId, $from, $to);

        return new AnalyticsOverview(
            from: $from,
            to: $to,
            conversationCount: $this->conversations->countConversations($scope->workspaceId, $from, $to),
            messageCount: $this->conversations->countMessages($scope->workspaceId, $from, $to),
            averageRating: $this->conversations->averageRating($scope->workspaceId, $from, $to),
            aiResolvedCount: $outcomes['ai_resolved'],
            escalatedCount: $outcomes['escalated'],
            unansweredCount: $outcomes['unanswered'],
        );
    }
}
