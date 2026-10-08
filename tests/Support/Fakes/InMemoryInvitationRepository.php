<?php

namespace Tests\Support\Fakes;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Contracts\InvitationRepositoryContract;
use App\Models\WorkspaceInvitation;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class InMemoryInvitationRepository implements InvitationRepositoryContract
{
    /** @var array<string, WorkspaceInvitation> indexées par « espace|adresse » */
    private array $invitations = [];

    public function pendingFor(string $workspaceId): Collection
    {
        return new Collection(array_values(array_filter(
            $this->invitations,
            fn (WorkspaceInvitation $invitation) => $invitation->workspace_id === $workspaceId && $invitation->isPending(),
        )));
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $invitationId): WorkspaceInvitation
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->id === $invitationId && $invitation->workspace_id === $workspaceId) {
                return $invitation;
            }
        }

        throw new ModelNotFoundException;
    }

    public function findByTokenHash(string $tokenHash): ?WorkspaceInvitation
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->token_hash === $tokenHash) {
                return $invitation;
            }
        }

        return null;
    }

    public function issue(
        string $workspaceId,
        string $email,
        WorkspaceRole $role,
        string $tokenHash,
        string $invitedByUserId,
        DateTimeInterface $expiresAt,
    ): WorkspaceInvitation {
        return $this->invitations["{$workspaceId}|{$email}"] = ModelFactory::invitation(
            $workspaceId,
            $email,
            $role,
            $tokenHash,
            $expiresAt,
        );
    }

    public function markAccepted(WorkspaceInvitation $invitation): WorkspaceInvitation
    {
        $invitation->setRawAttributes(['accepted_at' => '2026-01-01 00:00:00'] + $invitation->getAttributes(), true);

        return $invitation;
    }

    public function delete(WorkspaceInvitation $invitation): void
    {
        unset($this->invitations["{$invitation->workspace_id}|{$invitation->email}"]);
    }

    public function count(): int
    {
        return count($this->invitations);
    }
}
