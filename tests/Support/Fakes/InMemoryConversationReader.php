<?php

namespace Tests\Support\Fakes;

use App\Domains\Conversations\Contracts\ConversationReaderContract;
use Illuminate\Support\Carbon;

final class InMemoryConversationReader implements ConversationReaderContract
{
    public function __construct(
        private readonly int $conversationCount = 0,
        private readonly int $messageCount = 0,
        private readonly ?float $averageRating = null,
        private readonly int $aiResolved = 0,
        private readonly int $escalated = 0,
        private readonly int $unanswered = 0,
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
}
