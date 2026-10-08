<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Exceptions\InvalidVerificationTokenException;
use App\Domains\Auth\Support\EmailVerificationToken;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Models\User;

final class EmailVerificationService
{
    public function __construct(
        private readonly EmailVerificationToken $tokens,
        private readonly UserRepositoryContract $users,
        private readonly TransactionalMailerContract $mailer,
    ) {}

    /** Sans effet pour une adresse déjà vérifiée. */
    public function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $this->mailer->sendEmailVerification(
            $user->email,
            $user->name,
            $this->tokens->issue($user->id, $user->email, now()),
        );
    }

    /**
     * Rejouer un lien déjà utilisé n'est pas une erreur : l'adresse est
     * vérifiée, c'est ce que l'appelant voulait savoir.
     *
     * @throws InvalidVerificationTokenException
     */
    public function verify(string $token): User
    {
        $now = now();
        $userId = $this->tokens->userId($token, $now);
        $user = $userId === null ? null : $this->users->find($userId);

        if ($user === null || ! $this->tokens->matchesEmail($token, $user->email, $now)) {
            throw InvalidVerificationTokenException::make();
        }

        return $user->hasVerifiedEmail() ? $user : $this->users->markEmailVerified($user);
    }
}
