<?php

namespace App\Domains\Knowledge\Services;

use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Exceptions\DocumentNotRetryableException;
use App\Domains\Knowledge\Exceptions\QaContentRequiredException;
use App\Domains\Knowledge\Jobs\IndexKnowledgeDocumentJob;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class KnowledgeDocumentService
{
    public function __construct(
        private readonly KnowledgeDocumentRepositoryContract $documents,
        private readonly KnowledgeSourceRepositoryContract $sources,
        private readonly KnowledgeChunkRepositoryContract $chunks,
        private readonly KnowledgeFileStorage $files,
    ) {}

    /** @param  array<string, mixed>  $filters */
    public function list(WorkspaceScope $scope, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->documents->paginate($scope->workspaceId, $filters, $perPage);
    }

    public function get(WorkspaceScope $scope, string $documentId): KnowledgeDocument
    {
        return $this->documents->findInWorkspaceOrFail($scope->workspaceId, $documentId);
    }

    public function delete(WorkspaceScope $scope, string $documentId): void
    {
        $document = $this->documents->findInWorkspaceOrFail($scope->workspaceId, $documentId);

        if ($document->disk_path !== null) {
            $this->files->delete($document->disk_path);
        }

        $this->documents->delete($document);
    }

    /** @throws DocumentNotRetryableException */
    public function retry(WorkspaceScope $scope, string $documentId): KnowledgeDocument
    {
        $document = $this->documents->findInWorkspaceOrFail($scope->workspaceId, $documentId);

        if (! $document->isRetryable()) {
            throw DocumentNotRetryableException::make();
        }

        $document = $this->documents->update($document, [
            'status' => KnowledgeDocumentStatus::Pending,
            'status_message' => null,
        ]);

        IndexKnowledgeDocumentJob::dispatch($document->id);

        return $document;
    }

    /** @throws QaContentRequiredException */
    public function createQaEntry(WorkspaceScope $scope, string $question, string $answer, string $userId): KnowledgeDocument
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' || $answer === '') {
            throw QaContentRequiredException::make();
        }

        $source = $this->sources->findManualSource($scope->workspaceId)
            ?? $this->sources->create([
                'workspace_id' => $scope->workspaceId,
                'type' => KnowledgeSourceType::Manual,
                'name' => 'Entrées manuelles',
                'created_by_user_id' => $userId,
            ]);

        $document = $this->documents->create([
            'workspace_id' => $scope->workspaceId,
            'source_id' => $source->id,
            'type' => KnowledgeDocumentType::Qa,
            'title' => mb_substr($question, 0, 120),
            'question' => $question,
            'answer' => $answer,
            'raw_content' => "Q: {$question}\nA: {$answer}",
            'status' => KnowledgeDocumentStatus::Pending,
            'created_by_user_id' => $userId,
        ]);

        IndexKnowledgeDocumentJob::dispatch($document->id);

        return $document;
    }

    /** @throws QaContentRequiredException */
    public function updateQaEntry(WorkspaceScope $scope, string $documentId, string $question, string $answer): KnowledgeDocument
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' || $answer === '') {
            throw QaContentRequiredException::make();
        }

        $document = $this->documents->findInWorkspaceOrFail($scope->workspaceId, $documentId);

        $document = $this->documents->update($document, [
            'title' => mb_substr($question, 0, 120),
            'question' => $question,
            'answer' => $answer,
            'raw_content' => "Q: {$question}\nA: {$answer}",
            'status' => KnowledgeDocumentStatus::Pending,
            'status_message' => null,
        ]);

        $this->chunks->replaceForDocument($document->id, $document->workspace_id, []);

        IndexKnowledgeDocumentJob::dispatch($document->id);

        return $document;
    }
}
