<?php

namespace App\Domains\Auth\Support;

use DateTimeInterface;

/**
 * Jeton de vérification d'adresse e-mail : signé, borné dans le temps, sans
 * état côté serveur.
 *
 * Il lie un compte à l'adresse qu'il portait au moment de l'émission : si
 * l'adresse change entre-temps, l'ancien lien ne vérifie plus rien.
 */
final readonly class EmailVerificationToken
{
    private const ALGORITHM = 'sha256';

    public function __construct(
        private string $signingKey,
        private int $ttlMinutes,
    ) {}

    public function issue(string $userId, string $email, DateTimeInterface $now): string
    {
        $payload = self::encode(json_encode([
            'u' => $userId,
            'e' => self::emailFingerprint($email),
            'x' => $now->getTimestamp() + $this->ttlMinutes * 60,
        ], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * L'identifiant du compte désigné par le jeton, ou `null` si le jeton est
     * falsifié, mal formé ou expiré.
     */
    public function userId(string $token, DateTimeInterface $now): ?string
    {
        $claims = $this->claims($token, $now);

        return $claims['u'] ?? null;
    }

    /** Le jeton, valide, a-t-il été émis pour cette adresse ? */
    public function matchesEmail(string $token, string $email, DateTimeInterface $now): bool
    {
        $claims = $this->claims($token, $now);

        return $claims !== null && hash_equals($claims['e'], self::emailFingerprint($email));
    }

    /** @return array{u: string, e: string, x: int}|null */
    private function claims(string $token, DateTimeInterface $now): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals($this->sign($parts[0]), $parts[1])) {
            return null;
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $claims = $decoded === false ? null : json_decode($decoded, true);

        if (! is_array($claims)
            || ! is_string($claims['u'] ?? null)
            || ! is_string($claims['e'] ?? null)
            || ! is_int($claims['x'] ?? null)
            || $claims['x'] < $now->getTimestamp()) {
            return null;
        }

        return ['u' => $claims['u'], 'e' => $claims['e'], 'x' => $claims['x']];
    }

    private function sign(string $payload): string
    {
        return hash_hmac(self::ALGORITHM, $payload, $this->signingKey);
    }

    private static function emailFingerprint(string $email): string
    {
        return hash(self::ALGORITHM, mb_strtolower(trim($email)));
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
