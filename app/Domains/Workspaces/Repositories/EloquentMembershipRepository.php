<?php

namespace App\Domains\Workspaces\Repositories;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\Sort;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Models\WorkspaceMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentMembershipRepository implements MembershipRepositoryContract
{
    /** Colonnes du compte utiles à l'affichage d'un membre. */
    private const USER_COLUMNS = 'user:id,name,email,last_login_at';

    /** @var array<string, string|array{0: string, 1: string}> */
    private const SORTABLE = [
        'role' => 'role',
        'joined_at' => 'created_at',
    ];

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return WorkspaceMember::query()
            ->with(self::USER_COLUMNS)
            ->where('workspace_id', $workspaceId)
            ->when($filters['role'] ?? null, fn (Builder $query, mixed $role) => $query->where('role', $role))
            ->when(is_string($search) && $search !== '', fn (Builder $query) => $query->whereHas(
                'user',
                fn (Builder $user) => $user->where(fn (Builder $match) => $match
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")),
            ))
            ->tap(fn (Builder $query) => Sort::apply($query, $filters, self::SORTABLE, 'created_at', 'asc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $memberId): WorkspaceMember
    {
        return WorkspaceMember::query()
            ->with(self::USER_COLUMNS)
            ->where('workspace_id', $workspaceId)
            ->findOrFail($memberId);
    }

    public function forUser(string $userId): Collection
    {
        return WorkspaceMember::query()
            ->with('workspace')
            ->where('user_id', $userId)
            ->oldest()
            ->orderBy('id')
            ->get();
    }

    public function findForUser(string $workspaceId, string $userId): ?WorkspaceMember
    {
        return WorkspaceMember::query()
            ->with('workspace')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();
    }

    public function add(string $workspaceId, string $userId, WorkspaceRole $role): WorkspaceMember
    {
        return WorkspaceMember::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function changeRole(WorkspaceMember $member, WorkspaceRole $role): WorkspaceMember
    {
        $member->update(['role' => $role]);

        return $member;
    }

    public function remove(WorkspaceMember $member): void
    {
        $member->delete();
    }
}
