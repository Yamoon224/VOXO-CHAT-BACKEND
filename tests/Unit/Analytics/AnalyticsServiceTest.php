<?php

namespace Tests\Unit\Analytics;

use App\Domains\Analytics\Services\AnalyticsService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryConversationReader;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    #[Test]
    public function la_vue_d_ensemble_agrege_ce_que_le_lecteur_de_conversations_expose(): void
    {
        $reader = new InMemoryConversationReader(
            conversationCount: 12, messageCount: 48, averageRating: 4.5,
            aiResolved: 8, escalated: 3, unanswered: 1,
        );
        $service = new AnalyticsService($reader);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Owner);
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-10-01');

        $overview = $service->overview($scope, $from, $to);

        $this->assertSame(12, $overview->conversationCount);
        $this->assertSame(48, $overview->messageCount);
        $this->assertSame(4.5, $overview->averageRating);
        $this->assertSame(8, $overview->aiResolvedCount);
        $this->assertSame(3, $overview->escalatedCount);
        $this->assertSame(1, $overview->unansweredCount);
        $this->assertTrue($from->equalTo($overview->from));
        $this->assertTrue($to->equalTo($overview->to));
    }
}
