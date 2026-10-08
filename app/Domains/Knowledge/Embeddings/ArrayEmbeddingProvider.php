<?php

namespace App\Domains\Knowledge\Embeddings;

use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;

/**
 * Doublure de test : pas d'appel réseau, vecteurs calculés par un hachage de
 * sac de mots. Deux textes qui partagent du vocabulaire obtiennent une
 * similarité cosinus élevée, ce qui permet de tester le classement de la
 * recherche sémantique sans dépendre d'un vrai modèle d'embeddings.
 */
final class ArrayEmbeddingProvider implements EmbeddingProviderContract
{
    private const DIMENSIONS = 32;

    /** @var list<list<string>> */
    private array $calls = [];

    public function embed(array $texts): array
    {
        $this->calls[] = $texts;

        return array_map($this->embedOne(...), $texts);
    }

    public function modelName(): string
    {
        return 'array-test-embeddings';
    }

    /** @return list<list<string>> */
    public function calls(): array
    {
        return $this->calls;
    }

    /** @return list<float> */
    private function embedOne(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);

        $words = preg_split('/\W+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $bucket = crc32($word) % self::DIMENSIONS;
            $vector[$bucket] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn (float $v) => $v ** 2, $vector)));

        if ($norm === 0.0) {
            return $vector;
        }

        return array_map(fn (float $v) => $v / $norm, $vector);
    }
}
