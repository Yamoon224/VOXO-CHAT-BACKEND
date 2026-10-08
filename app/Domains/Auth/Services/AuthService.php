<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\DTOs\IssuedSession;
use App\Domains\Auth\Exceptions\AccountDisabledException;
use App\Domains\Auth\Exceptions\InvalidCredentialsException;
use App\Domains\Auth\Exceptions\InvalidTwoFactorCodeException;
use App\Domains\Auth\Exceptions\TwoFactorRequiredException;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Connexion par jeton Sanctum.
 *
 * Le frontend et l'API vivent sur des origines distinctes sans session
 * partagée : le client envoie `Authorization: Bearer {token}`.
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly MembershipReaderContract $memberships,
        private readonly AccessTokenManagerContract $tokens,
        private readonly TwoFactorService $twoFactor,
        private readonly Hasher $hasher,
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws AccountDisabledException
     * @throws TwoFactorRequiredException
     * @throws InvalidTwoFactorCodeException
     */
    public function attempt(string $email, string $password, ?string $code, string $deviceName): IssuedSession
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! $this->hasher->check($password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        if (! $user->is_active) {
            throw AccountDisabledException::make();
        }

        $this->twoFactor->assertLoginCode($user, $code);

        return $this->open($this->users->recordLogin($user), $deviceName);
    }

    public function logout(User $user): void
    {
        $this->tokens->revokeCurrent($user);
    }

    /**
     * La session s'ouvre sur le premier espace rejoint par le compte ; il en
     * change ensuite par une bascule explicite.
     */
    private function open(User $user, string $deviceName): IssuedSession
    {
        $workspaceId = $this->memberships->forUser($user->id)->first()?->workspace_id;

        return new IssuedSession($user, $this->tokens->issue($user, $workspaceId, $deviceName), $workspaceId);
    }
}
