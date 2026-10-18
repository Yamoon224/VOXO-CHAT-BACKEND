<?php

namespace Tests\Unit\Assistant;

use App\Domains\Assistant\Exceptions\ToolCallFailedException;
use App\Domains\Assistant\Providers\HttpAgentToolCaller;
use App\Models\AgentTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HttpAgentToolCallerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_argument_de_chemin_est_encode_avant_de_rejoindre_l_url(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $tool = AgentTool::factory()->make([
            'url' => 'https://exemple.fr/api/orders/{order_id}',
            'parameters' => [['name' => 'order_id', 'type' => 'string', 'description' => '', 'required' => true, 'in' => 'path']],
        ]);

        (new HttpAgentToolCaller)->call($tool, ['order_id' => '../admin?x=1']);

        Http::assertSent(fn ($request) => $request->url() === 'https://exemple.fr/api/orders/..%2Fadmin%3Fx%3D1');
    }

    #[Test]
    public function un_argument_de_requete_est_envoye_en_parametre_de_query(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $tool = AgentTool::factory()->make([
            'http_method' => 'GET',
            'url' => 'https://exemple.fr/api/search',
            'parameters' => [['name' => 'q', 'type' => 'string', 'description' => '', 'required' => true, 'in' => 'query']],
        ]);

        (new HttpAgentToolCaller)->call($tool, ['q' => 'café']);

        Http::assertSent(fn ($request) => $request->url() === 'https://exemple.fr/api/search?q=caf%C3%A9');
    }

    #[Test]
    public function les_en_tetes_declares_sont_envoyes_tels_quels(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $tool = AgentTool::factory()->make(['headers' => ['Authorization' => 'Bearer abc']]);

        (new HttpAgentToolCaller)->call($tool, []);

        Http::assertSent(fn ($request) => $request->header('Authorization') === ['Bearer abc']);
    }

    #[Test]
    public function une_reponse_en_echec_leve_une_exception_de_domaine(): void
    {
        Http::fake(['*' => Http::response('erreur', 500)]);
        $tool = AgentTool::factory()->make();

        $this->expectException(ToolCallFailedException::class);

        (new HttpAgentToolCaller)->call($tool, []);
    }

    #[Test]
    public function le_resultat_est_tronque_a_une_longueur_maximale(): void
    {
        Http::fake(['*' => Http::response(str_repeat('a', 5000), 200)]);
        $tool = AgentTool::factory()->make();

        $result = (new HttpAgentToolCaller)->call($tool, []);

        $this->assertSame(4000, mb_strlen($result));
    }
}
