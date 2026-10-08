<?php

namespace Tests\Support\Fakes;

use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class InMemoryUserRepository implements UserRepositoryContract
{
    /** @var array<string, User> */
    private array $users = [];

    public function __construct(User ...$users)
    {
        foreach ($users as $user) {
            $this->users[$user->id] = $user;
        }
    }

    public function findOrFail(string $id): User
    {
        return $this->users[$id] ?? throw new ModelNotFoundException;
    }

    public function find(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === mb_strtolower(trim($email))) {
                return $user;
            }
        }

        return null;
    }

    public function create(array $attributes, bool $emailVerified = false): User
    {
        $user = ModelFactory::user([
            'name' => $attributes['name'],
            'email' => mb_strtolower(trim($attributes['email'])),
            'password' => $attributes['password'],
            'locale' => $attributes['locale'] ?? 'fr',
            'email_verified_at' => $emailVerified ? '2026-01-01 00:00:00' : null,
        ]);

        return $this->users[$user->id] = $user;
    }

    public function update(User $user, array $attributes): User
    {
        return $this->patch($user, $attributes);
    }

    public function markEmailVerified(User $user): User
    {
        return $this->patch($user, ['email_verified_at' => '2026-01-01 00:00:00']);
    }

    public function recordLogin(User $user): User
    {
        return $this->patch($user, ['last_login_at' => '2026-01-01 00:00:00']);
    }

    public function storeTwoFactorSecret(User $user, ?string $secret): User
    {
        $this->patch($user, ['two_factor_confirmed_at' => null]);
        // Par l'accesseur et non en brut : l'attribut est chiffré au repos.
        $user->two_factor_secret = $secret;

        return $user;
    }

    public function confirmTwoFactor(User $user): User
    {
        return $this->patch($user, ['two_factor_confirmed_at' => '2026-01-01 00:00:00']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function patch(User $user, array $attributes): User
    {
        $user->setRawAttributes($attributes + $user->getAttributes(), true);

        return $this->users[$user->id] = $user;
    }
}
