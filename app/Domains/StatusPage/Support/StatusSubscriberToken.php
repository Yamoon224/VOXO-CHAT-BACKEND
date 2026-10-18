<?php

namespace App\Domains\StatusPage\Support;

/**
 * Jeton de désinscription des alertes de la page de statut : signé, sans
 * expiration (une alerte peut arriver des mois après l'inscription, le lien
 * de désabonnement doit rester valide aussi longtemps) — même famille que
 * `TicketPortalToken`, sans le `x` (expiration) puisqu'il n'y en a pas ici.
 */
final readonly class StatusSubscriberToken
{
    private const ALGORITHM = 'sha256';

    public function __construct(private string $signingKey) {}

    public function issue(string $workspaceId, string $subscriberId): string
    {
        $payload = self::encode(json_encode(['w' => $workspaceId, 's' => $subscriberId], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->sign($payload);
    }

    /** @return array{workspaceId: string, subscriberId: string}|null */
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals($this->sign($parts[0]), $parts[1])) {
            return null;
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $claims = $decoded === false ? null : json_decode($decoded, true);

        if (! is_array($claims) || ! is_string($claims['w'] ?? null) || ! is_string($claims['s'] ?? null)) {
            return null;
        }

        return ['workspaceId' => $claims['w'], 'subscriberId' => $claims['s']];
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
