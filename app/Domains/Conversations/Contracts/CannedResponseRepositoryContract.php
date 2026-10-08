<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\CannedResponse;
use Illuminate\Support\Collection;

interface CannedResponseRepositoryContract
{
    /** @return Collection<int, CannedResponse> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): CannedResponse;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): CannedResponse;

    /** @param  array<string, mixed>  $attributes */
    public function update(CannedResponse $response, array $attributes): CannedResponse;

    public function delete(CannedResponse $response): void;
}
