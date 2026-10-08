<?php

namespace App\Domains\Auth\Support;

use App\Domains\Auth\Contracts\PasswordResetTokenStoreContract;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;

/**
 * S'appuie sur le courtier de mots de passe de Laravel : jetons hachés,
 * expiration et délai entre deux demandes viennent de `config/auth.php`.
 */
final class BrokerPasswordResetTokenStore implements PasswordResetTokenStoreContract
{
    public function __construct(private readonly PasswordBroker $broker) {}

    public function create(User $user): ?string
    {
        if ($this->broker->getRepository()->recentlyCreatedToken($user)) {
            return null;
        }

        return $this->broker->createToken($user);
    }

    public function isValid(User $user, string $token): bool
    {
        return $this->broker->tokenExists($user, $token);
    }

    public function forget(User $user): void
    {
        $this->broker->deleteToken($user);
    }
}
