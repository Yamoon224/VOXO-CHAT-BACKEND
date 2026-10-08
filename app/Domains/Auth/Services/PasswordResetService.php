<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\Contracts\PasswordResetTokenStoreContract;
use App\Domains\Auth\Exceptions\InvalidResetTokenException;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Users\Contracts\UserRepositoryContract;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly PasswordResetTokenStoreContract $resetTokens,
        private readonly AccessTokenManagerContract $accessTokens,
        private readonly TransactionalMailerContract $mailer,
    ) {}

    /**
     * Ne dit jamais si l'adresse correspond à un compte : la réponse est la
     * même dans tous les cas, et seul le titulaire de la boîte en sait plus.
     */
    public function requestLink(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! $user->is_active) {
            return;
        }

        $token = $this->resetTokens->create($user);

        if ($token !== null) {
            $this->mailer->sendPasswordReset($user->email, $user->name, $token);
        }
    }

    /**
     * Change le mot de passe et ferme toutes les sessions ouvertes : si le
     * compte a été compromis, l'intrus est déconnecté en même temps.
     *
     * @throws InvalidResetTokenException
     */
    public function reset(string $email, string $token, string $password): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! $this->resetTokens->isValid($user, $token)) {
            throw InvalidResetTokenException::make();
        }

        $this->users->update($user, ['password' => $password]);
        $this->resetTokens->forget($user);
        $this->accessTokens->revokeAll($user);
    }
}
