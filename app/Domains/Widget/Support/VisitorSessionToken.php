<?php

namespace App\Domains\Widget\Support;

use DateTimeInterface;

/**
 * Jeton de session de visiteur : signé, borné dans le temps, sans état côté
 * serveur — même famille que `EmailVerificationToken` (domaine `Auth`) et
 * `InvitationToken` (domaine `Workspaces`), mais pas de table de sessions :
 * un visiteur anonyme n'a rien d'autre à retrouver que son identifiant.
 */
final readonly class VisitorSessionToken
{
    private const ALGORITHM = 'sha256';

    public function __construct(
        private string $signingKey,
        private int $ttlMinutes,
    ) {}

    public function issue(string $workspaceId, string $visitorId, DateTimeInterface $now): string
    {
        $payload = self::encode(json_encode([
            'w' => $workspaceId,
            'v' => $visitorId,
            'x' => $now->getTimestamp() + $this->ttlMinutes * 60,
        ], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Le périmètre désigné par le jeton, ou `null` s'il est falsifié, mal
     * formé ou expiré.
     *
     * @return array{workspaceId: string, visitorId: string}|null
     */
    public function verify(string $token, DateTimeInterface $now): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals($this->sign($parts[0]), $parts[1])) {
            return null;
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $claims = $decoded === false ? null : json_decode($decoded, true);

        if (! is_array($claims)
            || ! is_string($claims['w'] ?? null)
            || ! is_string($claims['v'] ?? null)
            || ! is_int($claims['x'] ?? null)
            || $claims['x'] < $now->getTimestamp()) {
            return null;
        }

        return ['workspaceId' => $claims['w'], 'visitorId' => $claims['v']];
    }

    private function sign(string $payload): string
    {
        return hash_hmac(self::ALGORITHM, $payload, $this->signingKey);
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
