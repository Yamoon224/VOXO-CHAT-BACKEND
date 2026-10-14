<?php

namespace App\Domains\Tickets\Support;

use DateTimeInterface;

/**
 * Jeton de portail client : signé, borné dans le temps, sans état côté
 * serveur — même famille que `VisitorSessionToken` (domaine `Widget`), mais
 * porte un contact plutôt qu'un visiteur, et vit bien plus longtemps (pas de
 * compte portail : ce jeton, reçu une fois par e-mail, est le seul accès du
 * client à l'historique de ses tickets — voir la décision d'architecture du
 * 2026-10-13, section 2.7 du cahier des charges).
 */
final readonly class TicketPortalToken
{
    private const ALGORITHM = 'sha256';

    public function __construct(
        private string $signingKey,
        private int $ttlDays,
    ) {}

    public function issue(string $workspaceId, string $contactId, DateTimeInterface $now): string
    {
        $payload = self::encode(json_encode([
            'w' => $workspaceId,
            'c' => $contactId,
            'x' => $now->getTimestamp() + $this->ttlDays * 86400,
        ], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Le périmètre désigné par le jeton, ou `null` s'il est falsifié, mal
     * formé ou expiré.
     *
     * @return array{workspaceId: string, contactId: string}|null
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
            || ! is_string($claims['c'] ?? null)
            || ! is_int($claims['x'] ?? null)
            || $claims['x'] < $now->getTimestamp()) {
            return null;
        }

        return ['workspaceId' => $claims['w'], 'contactId' => $claims['c']];
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
