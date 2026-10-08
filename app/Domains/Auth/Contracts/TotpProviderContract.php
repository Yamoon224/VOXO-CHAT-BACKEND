<?php

namespace App\Domains\Auth\Contracts;

/**
 * Codes à usage unique fondés sur le temps (TOTP, RFC 6238).
 */
interface TotpProviderContract
{
    public function generateSecret(): string;

    /** URI `otpauth://` à afficher en QR code dans l'application d'authentification. */
    public function provisioningUri(string $accountName, string $secret): string;

    public function verify(string $secret, string $code): bool;
}
