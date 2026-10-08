<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\TotpProviderContract;
use App\Domains\Auth\Exceptions\InvalidPasswordException;
use App\Domains\Auth\Exceptions\InvalidTwoFactorCodeException;
use App\Domains\Auth\Exceptions\TwoFactorNotStartedException;
use App\Domains\Auth\Exceptions\TwoFactorRequiredException;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Double authentification par code TOTP, lu dans une application
 * d'authentification : rien à envoyer, rien à recevoir.
 */
final class TwoFactorService
{
    public function __construct(
        private readonly TotpProviderContract $totp,
        private readonly UserRepositoryContract $users,
        private readonly Hasher $hasher,
    ) {}

    /**
     * Démarre ou redémarre une activation. Le secret reste sans effet sur la
     * connexion tant qu'un premier code valide ne l'a pas confirmé.
     *
     * @return array{secret: string, otpauth_url: string}
     */
    public function startEnrollment(User $user): array
    {
        $secret = $this->totp->generateSecret();
        $this->users->storeTwoFactorSecret($user, $secret);

        return [
            'secret' => $secret,
            'otpauth_url' => $this->totp->provisioningUri($user->email, $secret),
        ];
    }

    /**
     * @throws TwoFactorNotStartedException
     * @throws InvalidTwoFactorCodeException
     */
    public function confirmEnrollment(User $user, string $code): void
    {
        if ($user->two_factor_secret === null) {
            throw TwoFactorNotStartedException::make();
        }

        if (! $this->totp->verify($user->two_factor_secret, $code)) {
            throw InvalidTwoFactorCodeException::make();
        }

        $this->users->confirmTwoFactor($user);
    }

    /** @throws InvalidPasswordException */
    public function disable(User $user, string $password): void
    {
        if (! $this->hasher->check($password, $user->password)) {
            throw InvalidPasswordException::make();
        }

        $this->users->storeTwoFactorSecret($user, null);
    }

    /**
     * Sans effet pour un compte qui n'a pas activé la double authentification.
     *
     * @throws TwoFactorRequiredException
     * @throws InvalidTwoFactorCodeException
     */
    public function assertLoginCode(User $user, ?string $code): void
    {
        if (! $user->hasTwoFactorEnabled()) {
            return;
        }

        if ($code === null || trim($code) === '') {
            throw TwoFactorRequiredException::make();
        }

        if (! $this->totp->verify((string) $user->two_factor_secret, trim($code))) {
            throw InvalidTwoFactorCodeException::make();
        }
    }
}
