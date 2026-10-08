<?php

namespace App\Domains\Users\Contracts;

use App\Models\User;

interface UserRepositoryContract
{
    public function findOrFail(string $id): User;

    public function find(string $id): ?User;

    /** L'adresse est comparée en minuscules, comme elle est stockée. */
    public function findByEmail(string $email): ?User;

    /** @param  array{name: string, email: string, password: string, locale?: string}  $attributes */
    public function create(array $attributes, bool $emailVerified = false): User;

    /** @param  array<string, mixed>  $attributes */
    public function update(User $user, array $attributes): User;

    public function markEmailVerified(User $user): User;

    public function recordLogin(User $user): User;

    /** `null` efface le secret et sa confirmation. */
    public function storeTwoFactorSecret(User $user, ?string $secret): User;

    public function confirmTwoFactor(User $user): User;
}
