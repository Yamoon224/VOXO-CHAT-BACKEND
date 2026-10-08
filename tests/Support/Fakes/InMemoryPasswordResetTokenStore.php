<?php

namespace Tests\Support\Fakes;

use App\Domains\Auth\Contracts\PasswordResetTokenStoreContract;
use App\Models\User;

final class InMemoryPasswordResetTokenStore implements PasswordResetTokenStoreContract
{
    /** @var array<string, string> jeton par identifiant de compte */
    private array $tokens = [];

    public bool $throttled = false;

    public function create(User $user): ?string
    {
        if ($this->throttled) {
            return null;
        }

        return $this->tokens[$user->id] = 'reset-'.$user->id;
    }

    public function isValid(User $user, string $token): bool
    {
        return ($this->tokens[$user->id] ?? null) === $token;
    }

    public function forget(User $user): void
    {
        unset($this->tokens[$user->id]);
    }
}
