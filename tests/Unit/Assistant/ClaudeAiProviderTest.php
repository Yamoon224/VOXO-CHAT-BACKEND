<?php

namespace Tests\Unit\Assistant;

use App\Domains\Assistant\DTOs\ToolDefinition;
use App\Domains\Assistant\DTOs\ToolParameter;
use App\Domains\Assistant\Providers\ClaudeAiProvider;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\SpyToolInvoker;
use Tests\TestCase;

class ClaudeAiProviderTest extends TestCase
{
    private function provider(SpyToolInvoker $tools): ClaudeAiProvider
    {
        return new ClaudeAiProvider('fake-api-key', 'claude-sonnet-5-5', 'claude-haiku-4-5', 'https://api.anthropic.test/v1', $tools);
    }

    #[Test]
    public function un_appel_d_outil_est_execute_puis_sa_reponse_renvoyee_a_claude(): void
    {
        $tools = new SpyToolInvoker;
        $tools->willReturn('lookup_order', 'Commande #123 : expédiée.');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'content' => [['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'lookup_order', 'input' => ['order_id' => '123']]],
                    'stop_reason' => 'tool_use',
                ], 200)
                ->push([
                    'content' => [['type' => 'text', 'text' => '{"answer": "Votre commande a été expédiée.", "confidence": 0.9, "citation_indices": []}']],
                    'stop_reason' => 'end_turn',
                ], 200),
        ]);

        $toolDefinition = new ToolDefinition('lookup_order', 'Consulte une commande', [
            new ToolParameter('order_id', 'string', 'Identifiant', true),
        ]);

        $reply = $this->provider($tools)->respond('workspace-1', 'Tu es un agent.', [['role' => 'user', 'content' => 'Où est ma commande 123 ?']], [], [$toolDefinition]);

        $this->assertSame('Votre commande a été expédiée.', $reply->content);
        $this->assertSame(0.9, $reply->confidence);
        $this->assertSame(['lookup_order'], $reply->toolCallsUsed);

        $calls = $tools->calls();
        $this->assertCount(1, $calls);
        $this->assertSame('workspace-1', $calls[0]['workspaceId']);
        $this->assertSame('lookup_order', $calls[0]['toolName']);
        $this->assertSame(['order_id' => '123'], $calls[0]['arguments']);

        Http::assertSent(fn ($request) => ($request['tools'][0]['name'] ?? null) === 'lookup_order');
    }

    #[Test]
    public function sans_outil_disponible_la_reponse_finale_est_directe(): void
    {
        $tools = new SpyToolInvoker;

        Http::fake([
            '*' => Http::response([
                'content' => [['type' => 'text', 'text' => '{"answer": "Bonjour !", "confidence": 1.0, "citation_indices": []}']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        $reply = $this->provider($tools)->respond('workspace-1', 'Tu es un agent.', [['role' => 'user', 'content' => 'Bonjour']], []);

        $this->assertSame('Bonjour !', $reply->content);
        $this->assertSame([], $reply->toolCallsUsed);
        $this->assertSame([], $tools->calls());
    }

    #[Test]
    public function la_boucle_s_arrete_apres_le_nombre_maximal_d_iterations(): void
    {
        $tools = new SpyToolInvoker;
        $tools->willReturn('loopy', 'Résultat.');

        Http::fake([
            '*' => Http::response([
                'content' => [['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'loopy', 'input' => []]],
                'stop_reason' => 'tool_use',
            ], 200),
        ]);

        $toolDefinition = new ToolDefinition('loopy', 'Boucle toujours', []);
        $reply = $this->provider($tools)->respond('workspace-1', 'Tu es un agent.', [['role' => 'user', 'content' => 'Test']], [], [$toolDefinition]);

        // Jamais de réponse texte valide (toujours `tool_use`) : la confiance retombe à 0.
        $this->assertSame(0.0, $reply->confidence);
        $this->assertCount(3, $tools->calls());
        Http::assertSentCount(4);
    }
}
