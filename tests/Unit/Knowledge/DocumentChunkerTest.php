<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Support\DocumentChunker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DocumentChunkerTest extends TestCase
{
    #[Test]
    public function un_texte_court_tient_dans_un_seul_passage(): void
    {
        $chunks = DocumentChunker::chunk('Un texte court.', 1500, 150);

        $this->assertSame(['Un texte court.'], $chunks);
    }

    #[Test]
    public function un_texte_vide_ne_produit_aucun_passage(): void
    {
        $this->assertSame([], DocumentChunker::chunk('   '));
    }

    #[Test]
    public function un_texte_long_est_decoupe_en_plusieurs_passages_avec_recouvrement(): void
    {
        $text = implode(' ', array_fill(0, 500, 'mot'));

        $chunks = DocumentChunker::chunk($text, 100, 20);

        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(100, mb_strlen($chunk));
        }
    }
}
