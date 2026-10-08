<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Support\CosineSimilarity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CosineSimilarityTest extends TestCase
{
    #[Test]
    public function deux_vecteurs_identiques_ont_une_similarite_maximale(): void
    {
        $this->assertEqualsWithDelta(1.0, CosineSimilarity::between([1.0, 2.0, 3.0], [1.0, 2.0, 3.0]), 0.0001);
    }

    #[Test]
    public function deux_vecteurs_orthogonaux_ont_une_similarite_nulle(): void
    {
        $this->assertEqualsWithDelta(0.0, CosineSimilarity::between([1.0, 0.0], [0.0, 1.0]), 0.0001);
    }

    #[Test]
    public function un_vecteur_nul_ne_produit_aucune_division_par_zero(): void
    {
        $this->assertSame(0.0, CosineSimilarity::between([0.0, 0.0], [1.0, 1.0]));
    }
}
