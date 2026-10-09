<?php

namespace App\Domains\Analytics\DTOs;

use Illuminate\Support\Carbon;

final readonly class AnalyticsOverview
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $conversationCount,
        public int $messageCount,
        public ?float $averageRating,
        public int $aiResolvedCount,
        public int $escalatedCount,
        public int $unansweredCount,
    ) {}
}
