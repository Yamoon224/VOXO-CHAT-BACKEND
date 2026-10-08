<?php

namespace App\Domains\Knowledge\Support;

/**
 * Découpe un texte en passages de taille bornée, avec un recouvrement entre
 * passages consécutifs pour ne pas couper une idée en deux sans contexte
 * partagé.
 *
 * Découpage par caractères plutôt que par jeton réel : suffisant pour borner
 * la taille envoyée au fournisseur d'embeddings, sans dépendance à un
 * tokenizer spécifique à un modèle.
 */
final class DocumentChunker
{
    /** @return list<string> */
    public static function chunk(string $text, int $maxChars = 1500, int $overlapChars = 150): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        $chunks = [];
        $position = 0;
        $length = mb_strlen($text);

        while ($position < $length) {
            $slice = mb_substr($text, $position, $maxChars);
            $chunks[] = trim($slice);

            if ($position + $maxChars >= $length) {
                break;
            }

            $position += $maxChars - $overlapChars;
        }

        return array_values(array_filter($chunks, fn (string $chunk) => $chunk !== ''));
    }

    public static function estimateTokenCount(string $chunk): int
    {
        // Approximation usuelle : ~4 caractères par jeton pour un texte latin.
        return (int) ceil(mb_strlen($chunk) / 4);
    }
}
