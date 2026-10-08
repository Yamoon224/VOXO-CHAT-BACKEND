<?php

namespace App\Domains\Users\Repositories;

use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Models\User;

final class EloquentUserRepository implements UserRepositoryContract
{
    public function findOrFail(string $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function find(string $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function create(array $attributes, bool $emailVerified = false): User
    {
        $user = new User([
            'name' => $attributes['name'],
            'email' => mb_strtolower(trim($attributes['email'])),
            'password' => $attributes['password'],
            'locale' => $attributes['locale'] ?? 'fr',
            'is_active' => true,
        ]);

        // Hors de `$fillable` : la vérification ne se décrète pas depuis une
        // requête, elle se constate.
        $user->email_verified_at = $emailVerified ? now() : null;
        $user->save();

        return $user;
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function markEmailVerified(User $user): User
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function recordLogin(User $user): User
    {
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    public function storeTwoFactorSecret(User $user, ?string $secret): User
    {
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $user;
    }

    public function confirmTwoFactor(User $user): User
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user;
    }
}
