<?php

namespace App\Domains\Workspaces\Services;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Domains\Workspaces\Exceptions\OwnerProtectedException;
use App\Domains\Workspaces\Exceptions\RoleNotAssignableException;
use App\Domains\Workspaces\Exceptions\SelfMembershipChangeException;
use App\Models\WorkspaceMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Gestion de l'équipe d'un espace de travail.
 */
final class MemberService
{
    public function __construct(private readonly MembershipRepositoryContract $memberships) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, WorkspaceMember>
     */
    public function paginate(WorkspaceScope $scope, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->memberships->paginate($scope->workspaceId, $filters, $perPage);
    }

    /**
     * @throws RoleNotAssignableException
     * @throws SelfMembershipChangeException
     * @throws OwnerProtectedException
     */
    public function changeRole(WorkspaceScope $scope, string $memberId, WorkspaceRole $role): WorkspaceMember
    {
        if (! $role->isAssignable()) {
            throw RoleNotAssignableException::make();
        }

        $member = $this->modifiableMember($scope, $memberId);

        return $this->memberships->changeRole($member, $role);
    }

    /**
     * @throws SelfMembershipChangeException
     * @throws OwnerProtectedException
     */
    public function remove(WorkspaceScope $scope, string $memberId): void
    {
        $this->memberships->remove($this->modifiableMember($scope, $memberId));
    }

    /** Le membre visé, cherché dans l'espace de l'appelant, s'il peut être modifié par lui. */
    private function modifiableMember(WorkspaceScope $scope, string $memberId): WorkspaceMember
    {
        $member = $this->memberships->findInWorkspaceOrFail($scope->workspaceId, $memberId);

        if ($member->id === $scope->memberId) {
            throw SelfMembershipChangeException::make();
        }

        if ($member->isOwner()) {
            throw OwnerProtectedException::make();
        }

        return $member;
    }
}
