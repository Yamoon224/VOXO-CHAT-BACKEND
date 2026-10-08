<?php

namespace App\Domains\Workspaces\Contracts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\WorkspaceMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MembershipRepositoryContract extends MembershipReaderContract
{
    /**
     * Membres d'un espace, compte chargé.
     *
     * @param  array<string, mixed>  $filters  `role`, `search`, `sort`, `direction`
     * @return LengthAwarePaginator<int, WorkspaceMember>
     */
    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /** Le membre est cherché dans cet espace uniquement, compte chargé. */
    public function findInWorkspaceOrFail(string $workspaceId, string $memberId): WorkspaceMember;

    public function add(string $workspaceId, string $userId, WorkspaceRole $role): WorkspaceMember;

    public function changeRole(WorkspaceMember $member, WorkspaceRole $role): WorkspaceMember;

    public function remove(WorkspaceMember $member): void;
}
