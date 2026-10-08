<?php

namespace App\Domains\Knowledge\Contracts;

/**
 * Calcule les vecteurs d'embedding d'un lot de textes. Le fournisseur
 * (Voyage AI en production) est un réglage, pas une constante : voir
 * `config/knowledge.php`.
 */
interface EmbeddingProviderContract
{
    /**
     * @param  list<string>  $texts
     * @return list<list<float>> un vecteur par texte, dans le même ordre
     */
    public function embed(array $texts): array;

    public function modelName(): string;
}
