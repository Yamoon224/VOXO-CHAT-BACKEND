<?php

namespace Tests\Support\Fakes;

use App\Domains\Auth\Contracts\TotpProviderContract;

final class FakeTotpProvider implements TotpProviderContract
{
    public const VALID_CODE = '123456';

    public const SECRET = 'FAKESECRET234567';

    public function generateSecret(): string
    {
        return self::SECRET;
    }

    public function provisioningUri(string $accountName, string $secret): string
    {
        return "otpauth://totp/VOXO:{$accountName}?secret={$secret}";
    }

    public function verify(string $secret, string $code): bool
    {
        return $code === self::VALID_CODE;
    }
}
