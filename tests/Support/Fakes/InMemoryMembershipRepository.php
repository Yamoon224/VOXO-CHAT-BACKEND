<?php

namespace Tests\Support\Fakes;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Models\WorkspaceMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryMembershipRepository implements MembershipRepositoryContract
{
    /** @var array<string, WorkspaceMember> */
    private array $members = [];

    public function __construct(WorkspaceMember ...$members)
    {
        foreach ($members as $member) {
            $this->members[$member->id] = $member;
        }
    }

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $items = array_values(array_filter(
            $this->members,
            fn (WorkspaceMember $member) => $member->workspace_id === $workspaceId,
        ));

        return new Paginator($items, count($items), $perPage);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $memberId): WorkspaceMember
    {
        $member = $this->members[$memberId] ?? null;

        if ($member === null || $member->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $member;
    }

    public function forUser(string $userId): Collection
    {
        return new Collection(array_values(array_filter(
            $this->members,
            fn (WorkspaceMember $member) => $member->user_id === $userId,
        )));
    }

    public function findForUser(string $workspaceId, string $userId): ?WorkspaceMember
    {
        foreach ($this->members as $member) {
            if ($member->workspace_id === $workspaceId && $member->user_id === $userId) {
                return $member;
            }
        }

        return null;
    }

    public function add(string $workspaceId, string $userId, WorkspaceRole $role): WorkspaceMember
    {
        $member = ModelFactory::member($workspaceId, $userId, $role);

        return $this->members[$member->id] = $member;
    }

    public function changeRole(WorkspaceMember $member, WorkspaceRole $role): WorkspaceMember
    {
        $member->setRawAttributes(['role' => $role->value] + $member->getAttributes(), true);

        return $member;
    }

    public function remove(WorkspaceMember $member): void
    {
        unset($this->members[$member->id]);
    }

    public function count(): int
    {
        return count($this->members);
    }
}
