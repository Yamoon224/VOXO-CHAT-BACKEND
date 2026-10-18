<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\DTOs\AnalyticsOverview;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Support\Carbon;

/** Export CSV (section 2.12) : les mêmes agrégats que `/workspace/analytics/overview`, pour un tableur. */
final class AnalyticsExportService
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function export(WorkspaceScope $scope, Carbon $from, Carbon $to): string
    {
        $overview = $this->analytics->overview($scope, $from, $to);
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['section', 'key', 'value']);
        $this->writeSummary($handle, $overview);
        $this->writeBreakdown($handle, 'messages_by_agent', $overview->messagesByAgent, 'user_id');
        $this->writeBreakdown($handle, 'conversations_by_channel', $overview->conversationsByChannel, 'channel');
        $this->writeBreakdown($handle, 'top_cited_documents', $overview->topCitedDocuments, 'title');

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** @param  resource  $handle */
    private function writeSummary($handle, AnalyticsOverview $overview): void
    {
        fputcsv($handle, ['summary', 'from', $overview->from->toIso8601String()]);
        fputcsv($handle, ['summary', 'to', $overview->to->toIso8601String()]);
        fputcsv($handle, ['summary', 'conversation_count', $overview->conversationCount]);
        fputcsv($handle, ['summary', 'message_count', $overview->messageCount]);
        fputcsv($handle, ['summary', 'average_rating', $overview->averageRating]);
        fputcsv($handle, ['summary', 'ai_resolved_count', $overview->aiResolvedCount]);
        fputcsv($handle, ['summary', 'escalated_count', $overview->escalatedCount]);
        fputcsv($handle, ['summary', 'unanswered_count', $overview->unansweredCount]);
        fputcsv($handle, ['summary', 'average_first_response_seconds', $overview->averageFirstResponseSeconds]);
        fputcsv($handle, ['summary', 'average_resolution_seconds', $overview->averageResolutionSeconds]);
        fputcsv($handle, ['summary', 'unanswered_knowledge_questions_count', $overview->unansweredKnowledgeQuestionsCount]);
    }

    /**
     * @param  resource  $handle
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeBreakdown($handle, string $section, array $rows, string $keyField): void
    {
        foreach ($rows as $row) {
            fputcsv($handle, [$section, $row[$keyField], $row['count']]);
        }
    }
}
