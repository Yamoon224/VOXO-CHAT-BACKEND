<?php

namespace App\Domains\Auth\Support;

use App\Domains\Auth\Contracts\TotpProviderContract;
use PragmaRX\Google2FA\Google2FA;

final class Google2faTotpProvider implements TotpProviderContract
{
    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly string $issuer,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function provisioningUri(string $accountName, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($this->issuer, $accountName, $secret);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code) !== false;
    }
}
