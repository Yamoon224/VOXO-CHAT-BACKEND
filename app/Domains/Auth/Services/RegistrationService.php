<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\DTOs\IssuedSession;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Workspaces\Contracts\WorkspaceProvisionerContract;

/**
 * Inscription : un compte, son premier espace de travail dont il devient
 * propriétaire, et une session ouverte dessus.
 *
 * Le rôle est imposé ici et jamais lu dans la requête.
 */
final class RegistrationService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly WorkspaceProvisionerContract $workspaces,
        private readonly AccessTokenManagerContract $tokens,
        private readonly EmailVerificationService $verification,
        private readonly TransactionManagerContract $transactions,
    ) {}

    /** @param  array{name: string, email: string, password: string, workspace_name: string, locale?: string}  $data */
    public function register(array $data, string $deviceName): IssuedSession
    {
        $session = $this->transactions->run(function () use ($data, $deviceName): IssuedSession {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'locale' => $data['locale'] ?? 'fr',
            ]);

            $workspaceId = $this->workspaces->provision($user->id, $data['workspace_name'])->workspace_id;

            return new IssuedSession($user, $this->tokens->issue($user, $workspaceId, $deviceName), $workspaceId);
        });

        // Après la transaction : un e-mail ne s'annule pas si l'écriture échoue.
        $this->verification->send($session->user);

        return $session;
    }
}
