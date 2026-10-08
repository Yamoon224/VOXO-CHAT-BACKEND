<?php

namespace App\Domains\Workspaces\Repositories;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Contracts\InvitationRepositoryContract;
use App\Models\WorkspaceInvitation;
use DateTimeInterface;
use Illuminate\Support\Collection;

final class EloquentInvitationRepository implements InvitationRepositoryContract
{
    public function pendingFor(string $workspaceId): Collection
    {
        return WorkspaceInvitation::query()
            ->with('inviter:id,name')
            ->where('workspace_id', $workspaceId)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->orderBy('id')
            ->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $invitationId): WorkspaceInvitation
    {
        return WorkspaceInvitation::query()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($invitationId);
    }

    public function findByTokenHash(string $tokenHash): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::query()
            ->with(['workspace', 'inviter:id,name'])
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function issue(
        string $workspaceId,
        string $email,
        WorkspaceRole $role,
        string $tokenHash,
        string $invitedByUserId,
        DateTimeInterface $expiresAt,
    ): WorkspaceInvitation {
        return WorkspaceInvitation::query()->updateOrCreate(
            ['workspace_id' => $workspaceId, 'email' => $email],
            [
                'role' => $role,
                'token_hash' => $tokenHash,
                'invited_by_user_id' => $invitedByUserId,
                'expires_at' => $expiresAt,
                'accepted_at' => null,
            ],
        );
    }

    public function markAccepted(WorkspaceInvitation $invitation): WorkspaceInvitation
    {
        $invitation->update(['accepted_at' => now()]);

        return $invitation;
    }

    public function delete(WorkspaceInvitation $invitation): void
    {
        $invitation->delete();
    }
}
