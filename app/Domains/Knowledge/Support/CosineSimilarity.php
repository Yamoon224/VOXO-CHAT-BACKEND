<?php

namespace App\Domains\Knowledge\Support;

/**
 * Similarité cosinus entre deux vecteurs, calculée en PHP : c'est tout le
 * "moteur de recherche vectorielle" tant que la base applicative reste MySQL
 * (décision du 8 octobre 2026, section 12 du cahier des charges).
 */
final class CosineSimilarity
{
    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function between(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
