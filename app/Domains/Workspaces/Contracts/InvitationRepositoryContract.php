<?php

namespace App\Domains\Workspaces\Contracts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\WorkspaceInvitation;
use DateTimeInterface;
use Illuminate\Support\Collection;

interface InvitationRepositoryContract
{
    /**
     * Invitations en attente d'un espace, invitant chargé, les plus récentes
     * d'abord.
     *
     * @return Collection<int, WorkspaceInvitation>
     */
    public function pendingFor(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $invitationId): WorkspaceInvitation;

    /** Espace de travail et invitant chargés. */
    public function findByTokenHash(string $tokenHash): ?WorkspaceInvitation;

    /**
     * Crée l'invitation, ou renouvelle celle qui existe déjà pour cette
     * adresse dans cet espace : nouveau jeton, nouvelle échéance.
     */
    public function issue(
        string $workspaceId,
        string $email,
        WorkspaceRole $role,
        string $tokenHash,
        string $invitedByUserId,
        DateTimeInterface $expiresAt,
    ): WorkspaceInvitation;

    public function markAccepted(WorkspaceInvitation $invitation): WorkspaceInvitation;

    public function delete(WorkspaceInvitation $invitation): void;
}
