<?php

namespace App\Domains\Shared\Support;

/**
 * Taille de page demandée par le client, bornée côté serveur : sans plafond,
 * `per_page=100000` suffirait à faire charger une table entière.
 */
final class PageSize
{
    public const DEFAULT = 25;

    public const MAX = 100;

    public static function from(mixed $requested): int
    {
        if (! is_numeric($requested)) {
            return self::DEFAULT;
        }

        return max(1, min(self::MAX, (int) $requested));
    }
}
