<?php

namespace App\Domains\Conversations\Services;

use App\Domains\Conversations\Contracts\CannedResponseRepositoryContract;
use App\Domains\Conversations\Exceptions\CannedResponseTitleTakenException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\CannedResponse;
use Illuminate\Support\Collection;

final class CannedResponseService
{
    public function __construct(private readonly CannedResponseRepositoryContract $responses) {}

    /** @return Collection<int, CannedResponse> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->responses->forWorkspace($scope->workspaceId);
    }

    /** @throws CannedResponseTitleTakenException */
    public function create(WorkspaceScope $scope, string $title, string $body): CannedResponse
    {
        $this->assertTitleAvailable($scope->workspaceId, $title);

        return $this->responses->create(['workspace_id' => $scope->workspaceId, 'title' => $title, 'body' => $body]);
    }

    /** @throws CannedResponseTitleTakenException */
    public function update(WorkspaceScope $scope, string $responseId, string $title, string $body): CannedResponse
    {
        $response = $this->responses->findInWorkspaceOrFail($scope->workspaceId, $responseId);

        if ($title !== $response->title) {
            $this->assertTitleAvailable($scope->workspaceId, $title);
        }

        return $this->responses->update($response, ['title' => $title, 'body' => $body]);
    }

    public function delete(WorkspaceScope $scope, string $responseId): void
    {
        $this->responses->delete($this->responses->findInWorkspaceOrFail($scope->workspaceId, $responseId));
    }

    /** @throws CannedResponseTitleTakenException */
    private function assertTitleAvailable(string $workspaceId, string $title): void
    {
        if ($this->responses->forWorkspace($workspaceId)->contains(fn (CannedResponse $response) => $response->title === $title)) {
            throw CannedResponseTitleTakenException::make();
        }
    }
}
