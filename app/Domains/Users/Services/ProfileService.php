<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Users\Exceptions\CurrentPasswordMismatchException;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Le compte tel que son titulaire le gère lui-même.
 */
final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly Hasher $hasher,
    ) {}

    /** @param  array{name?: string, locale?: string}  $attributes */
    public function update(User $user, array $attributes): User
    {
        return $this->users->update($user, $attributes);
    }

    /** @throws CurrentPasswordMismatchException */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! $this->hasher->check($currentPassword, $user->password)) {
            throw CurrentPasswordMismatchException::make();
        }

        $this->users->update($user, ['password' => $newPassword]);
    }
}
