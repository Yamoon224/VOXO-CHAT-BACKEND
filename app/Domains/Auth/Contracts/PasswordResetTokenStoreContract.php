<?php

namespace App\Domains\Auth\Contracts;

use App\Models\User;

/**
 * Jetons de réinitialisation de mot de passe : émission bornée dans le temps,
 * vérification, usage unique.
 */
interface PasswordResetTokenStoreContract
{
    /** @return string|null le jeton en clair, ou `null` si une demande vient déjà d'être émise */
    public function create(User $user): ?string;

    public function isValid(User $user, string $token): bool;

    public function forget(User $user): void;
}
