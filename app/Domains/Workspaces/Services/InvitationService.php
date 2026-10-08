<?php

namespace App\Domains\Workspaces\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Workspaces\Contracts\InvitationRepositoryContract;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Domains\Workspaces\Contracts\WorkspaceRepositoryContract;
use App\Domains\Workspaces\DTOs\AcceptedInvitation;
use App\Domains\Workspaces\Exceptions\AccountDetailsRequiredException;
use App\Domains\Workspaces\Exceptions\AlreadyMemberException;
use App\Domains\Workspaces\Exceptions\InvitationEmailMismatchException;
use App\Domains\Workspaces\Exceptions\InvitationExpiredException;
use App\Domains\Workspaces\Exceptions\InvitationInvalidException;
use App\Domains\Workspaces\Exceptions\InvitationRequiresLoginException;
use App\Domains\Workspaces\Exceptions\RoleNotAssignableException;
use App\Domains\Workspaces\Support\InvitationToken;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Collection;

/**
 * Invitations à rejoindre un espace de travail : émission, révocation,
 * acceptation.
 */
final class InvitationService
{
    public function __construct(
        private readonly InvitationRepositoryContract $invitations,
        private readonly MembershipRepositoryContract $memberships,
        private readonly WorkspaceRepositoryContract $workspaces,
        private readonly UserRepositoryContract $users,
        private readonly AccessTokenManagerContract $tokens,
        private readonly TransactionalMailerContract $mailer,
        private readonly TransactionManagerContract $transactions,
        private readonly int $ttlHours,
    ) {}

    /** @return Collection<int, WorkspaceInvitation> */
    public function pending(WorkspaceScope $scope): Collection
    {
        return $this->invitations->pendingFor($scope->workspaceId);
    }

    /**
     * Invite une adresse, ou renouvelle son invitation en attente.
     *
     * @throws RoleNotAssignableException
     * @throws AlreadyMemberException
     */
    public function invite(WorkspaceScope $scope, User $inviter, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        if (! $role->isAssignable()) {
            throw RoleNotAssignableException::make();
        }

        $email = mb_strtolower(trim($email));
        $this->assertNotAlreadyMember($scope->workspaceId, $email);

        $token = InvitationToken::generate();

        $invitation = $this->invitations->issue(
            $scope->workspaceId,
            $email,
            $role,
            InvitationToken::hash($token),
            $inviter->id,
            now()->addHours($this->ttlHours),
        );

        $this->mailer->sendWorkspaceInvitation(
            $email,
            $this->workspaces->findOrFail($scope->workspaceId)->name,
            $inviter->name,
            $role->label(),
            $token,
        );

        return $invitation->setRelation('inviter', $inviter);
    }

    public function revoke(WorkspaceScope $scope, string $invitationId): void
    {
        $this->invitations->delete(
            $this->invitations->findInWorkspaceOrFail($scope->workspaceId, $invitationId),
        );
    }

    /**
     * L'invitation désignée par le jeton, si elle est encore utilisable.
     *
     * @throws InvitationInvalidException
     * @throws InvitationExpiredException
     */
    public function resolve(string $token): WorkspaceInvitation
    {
        $invitation = $this->invitations->findByTokenHash(InvitationToken::hash($token));

        if ($invitation === null) {
            throw InvitationInvalidException::make();
        }

        if (! $invitation->isPending()) {
            throw InvitationExpiredException::make();
        }

        return $invitation;
    }

    /** L'adresse invitée a-t-elle déjà un compte ? Décide du formulaire affiché à l'invité. */
    public function inviteeHasAccount(WorkspaceInvitation $invitation): bool
    {
        return $this->users->findByEmail($invitation->email) !== null;
    }

    /**
     * Accepte une invitation.
     *
     * Trois situations : l'invité est connecté avec le bon compte ; il n'a
     * pas de compte et en crée un ; il a un compte mais n'est pas connecté,
     * auquel cas il doit d'abord s'authentifier.
     *
     * @param  array{name?: string|null, password?: string|null}  $account
     *
     * @throws InvitationInvalidException
     * @throws InvitationExpiredException
     * @throws InvitationEmailMismatchException
     * @throws InvitationRequiresLoginException
     * @throws AccountDetailsRequiredException
     */
    public function accept(string $token, ?User $authenticated, array $account, string $deviceName): AcceptedInvitation
    {
        $invitation = $this->resolve($token);

        return $this->transactions->run(function () use ($invitation, $authenticated, $account, $deviceName): AcceptedInvitation {
            $user = $authenticated !== null
                ? $this->matchingAccount($invitation, $authenticated)
                : $this->newAccount($invitation, $account);

            $member = $this->memberships->findForUser($invitation->workspace_id, $user->id)
                ?? $this->memberships->add($invitation->workspace_id, $user->id, $invitation->role);

            $this->invitations->markAccepted($invitation);

            if ($authenticated !== null) {
                $this->tokens->bindCurrentToWorkspace($user, $invitation->workspace_id);

                return new AcceptedInvitation($member, null);
            }

            return new AcceptedInvitation(
                $member,
                $this->tokens->issue($user, $invitation->workspace_id, $deviceName),
            );
        });
    }

    private function assertNotAlreadyMember(string $workspaceId, string $email): void
    {
        $existing = $this->users->findByEmail($email);

        if ($existing !== null && $this->memberships->findForUser($workspaceId, $existing->id) !== null) {
            throw AlreadyMemberException::make();
        }
    }

    private function matchingAccount(WorkspaceInvitation $invitation, User $authenticated): User
    {
        if (mb_strtolower($authenticated->email) !== $invitation->email) {
            throw InvitationEmailMismatchException::make();
        }

        return $authenticated;
    }

    /** @param  array{name?: string|null, password?: string|null}  $account */
    private function newAccount(WorkspaceInvitation $invitation, array $account): User
    {
        if ($this->inviteeHasAccount($invitation)) {
            throw InvitationRequiresLoginException::make();
        }

        $name = $account['name'] ?? null;
        $password = $account['password'] ?? null;

        if ($name === null || $name === '' || $password === null || $password === '') {
            throw AccountDetailsRequiredException::make();
        }

        // L'invité a reçu le jeton dans sa boîte : la possession de l'adresse
        // est déjà prouvée, inutile de la lui faire confirmer une seconde fois.
        return $this->users->create(
            ['name' => $name, 'email' => $invitation->email, 'password' => $password],
            emailVerified: true,
        );
    }
}
