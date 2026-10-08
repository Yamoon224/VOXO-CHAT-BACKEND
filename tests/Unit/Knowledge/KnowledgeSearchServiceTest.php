<?php

namespace Tests\Unit\Knowledge;

use App\Domains\Knowledge\Embeddings\ArrayEmbeddingProvider;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Search\MySqlCosineSimilaritySearch;
use App\Domains\Knowledge\Services\KnowledgeSearchService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryKnowledgeChunkRepository;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\TestCase;

class KnowledgeSearchServiceTest extends TestCase
{
    #[Test]
    public function la_recherche_classe_le_passage_le_plus_proche_du_vocabulaire_de_la_requete(): void
    {
        $documents = new InMemoryKnowledgeDocumentRepository;
        $pricing = $documents->create([
            'workspace_id' => 'workspace-1', 'source_id' => 'source-1', 'type' => 'file', 'title' => 'Tarifs',
        ]);
        $shipping = $documents->create([
            'workspace_id' => 'workspace-1', 'source_id' => 'source-1', 'type' => KnowledgeDocumentType::WebsitePage,
            'title' => 'Livraison', 'origin_url' => 'https://example.test/livraison',
        ]);

        $embeddings = new ArrayEmbeddingProvider;
        $chunks = new InMemoryKnowledgeChunkRepository;

        $pricingChunk = $chunks->replaceForDocument($pricing->id, 'workspace-1', [['content' => 'Nos tarifs commencent à 29 euros par mois.']])->first();
        $chunks->saveEmbedding($pricingChunk, $embeddings->embed(['Nos tarifs commencent à 29 euros par mois.'])[0], 'test');

        $shippingChunk = $chunks->replaceForDocument($shipping->id, 'workspace-1', [['content' => 'La livraison prend deux à cinq jours ouvrés.']])->first();
        $chunks->saveEmbedding($shippingChunk, $embeddings->embed(['La livraison prend deux à cinq jours ouvrés.'])[0], 'test');

        $service = new KnowledgeSearchService($embeddings, new MySqlCosineSimilaritySearch($chunks), $documents);

        $results = $service->search(new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent), 'Quel est le prix par mois ?', 2);

        $this->assertNotEmpty($results);
        $this->assertSame('Tarifs', $results[0]->documentTitle);
        $this->assertNull($results[0]->citationUrl);
    }

    #[Test]
    public function une_page_web_porte_son_url_comme_citation(): void
    {
        $documents = new InMemoryKnowledgeDocumentRepository;
        $page = $documents->create([
            'workspace_id' => 'workspace-1', 'source_id' => 'source-1', 'type' => KnowledgeDocumentType::WebsitePage,
            'title' => 'FAQ', 'origin_url' => 'https://example.test/faq',
        ]);

        $embeddings = new ArrayEmbeddingProvider;
        $chunks = new InMemoryKnowledgeChunkRepository;
        $chunk = $chunks->replaceForDocument($page->id, 'workspace-1', [['content' => 'Question fréquente sur le remboursement.']])->first();
        $chunks->saveEmbedding($chunk, $embeddings->embed(['Question fréquente sur le remboursement.'])[0], 'test');

        $service = new KnowledgeSearchService($embeddings, new MySqlCosineSimilaritySearch($chunks), $documents);

        $results = $service->search(new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent), 'remboursement', 1);

        $this->assertSame('https://example.test/faq', $results[0]->citationUrl);
    }

    #[Test]
    public function une_requete_vide_ne_cherche_rien(): void
    {
        $embeddings = new ArrayEmbeddingProvider;
        $chunks = new InMemoryKnowledgeChunkRepository;
        $service = new KnowledgeSearchService($embeddings, new MySqlCosineSimilaritySearch($chunks), new InMemoryKnowledgeDocumentRepository);

        $results = $service->search(new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent), '   ');

        $this->assertSame([], $results);
    }
}
