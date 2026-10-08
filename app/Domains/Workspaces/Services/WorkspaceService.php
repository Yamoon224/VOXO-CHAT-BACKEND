<?php

namespace App\Domains\Workspaces\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Exceptions\WorkspaceScopeViolationException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Domains\Workspaces\Contracts\WorkspaceProvisionerContract;
use App\Domains\Workspaces\Contracts\WorkspaceRepositoryContract;
use App\Domains\Workspaces\Support\WorkspaceSlug;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Collection;

final class WorkspaceService implements WorkspaceProvisionerContract
{
    public function __construct(
        private readonly WorkspaceRepositoryContract $workspaces,
        private readonly MembershipRepositoryContract $memberships,
        private readonly AccessTokenManagerContract $tokens,
        private readonly TransactionManagerContract $transactions,
    ) {}

    public function provision(string $ownerUserId, string $name): WorkspaceMember
    {
        return $this->transactions->run(function () use ($ownerUserId, $name): WorkspaceMember {
            $workspace = $this->workspaces->create([
                'name' => $name,
                'slug' => $this->availableSlug($name),
            ]);

            $member = $this->memberships->add($workspace->id, $ownerUserId, WorkspaceRole::Owner);

            return $member->setRelation('workspace', $workspace);
        });
    }

    /** @return Collection<int, WorkspaceMember> */
    public function membershipsOf(User $user): Collection
    {
        return $this->memberships->forUser($user->id);
    }

    public function current(WorkspaceScope $scope): Workspace
    {
        return $this->workspaces->findOrFail($scope->workspaceId);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(WorkspaceScope $scope, array $attributes): Workspace
    {
        return $this->workspaces->update($this->current($scope), $attributes);
    }

    /**
     * Bascule la session en cours sur un autre espace de l'appelant.
     *
     * L'identifiant vient de la requête, mais il ne devient un périmètre
     * qu'après vérification de l'adhésion.
     *
     * @throws WorkspaceScopeViolationException
     */
    public function switchTo(User $user, string $workspaceId): WorkspaceMember
    {
        $member = $this->memberships->findForUser($workspaceId, $user->id);

        if ($member === null) {
            throw WorkspaceScopeViolationException::make();
        }

        $this->tokens->bindCurrentToWorkspace($user, $workspaceId);

        return $member;
    }

    /**
     * La boucle ne garantit pas l'unicité face à deux créations simultanées :
     * c'est l'index unique sur `slug` qui la garantit.
     */
    private function availableSlug(string $name): string
    {
        $base = WorkspaceSlug::base($name);
        $candidate = $base;

        while ($this->workspaces->slugExists($candidate)) {
            $candidate = WorkspaceSlug::withSuffix($base);
        }

        return $candidate;
    }
}
