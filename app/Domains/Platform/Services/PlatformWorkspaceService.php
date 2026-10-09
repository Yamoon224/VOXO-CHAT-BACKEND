<?php

namespace App\Domains\Platform\Services;

use App\Domains\Workspaces\Contracts\WorkspacePlatformReaderContract;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

final class PlatformWorkspaceService
{
    public function __construct(private readonly WorkspacePlatformReaderContract $workspaces) {}

    /** @return LengthAwarePaginator<int, Workspace> */
    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        return $this->workspaces->listAllPaginated($perPage);
    }
}
