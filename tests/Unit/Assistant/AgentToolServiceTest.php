<?php

namespace Tests\Unit\Assistant;

use App\Domains\Assistant\Contracts\ToolCallerContract;
use App\Domains\Assistant\Exceptions\ToolCallFailedException;
use App\Domains\Assistant\Services\AgentToolService;
use App\Models\AgentTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentToolServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invoquer_un_outil_inconnu_ne_leve_jamais_d_exception(): void
    {
        $caller = new class implements ToolCallerContract
        {
            public function call(AgentTool $tool, array $arguments): string
            {
                throw new LogicException('Ne devrait jamais être appelé.');
            }
        };

        $service = app()->makeWith(AgentToolService::class, ['caller' => $caller]);

        $result = $service->invoke('workspace-1', 'inconnu', []);

        $this->assertSame("Cet outil n'est pas disponible.", $result);
    }

    #[Test]
    public function invoquer_un_outil_desactive_ne_leve_jamais_d_exception(): void
    {
        $tool = AgentTool::factory()->create(['enabled' => false]);
        $caller = new class implements ToolCallerContract
        {
            public function call(AgentTool $tool, array $arguments): string
            {
                throw new LogicException('Ne devrait jamais être appelé.');
            }
        };

        $service = app()->makeWith(AgentToolService::class, ['caller' => $caller]);

        $result = $service->invoke($tool->workspace_id, $tool->name, []);

        $this->assertSame("Cet outil n'est pas disponible.", $result);
    }

    #[Test]
    public function un_appel_en_echec_renvoie_un_message_plutot_qu_une_exception(): void
    {
        $tool = AgentTool::factory()->create();
        $caller = new class implements ToolCallerContract
        {
            public function call(AgentTool $tool, array $arguments): string
            {
                throw ToolCallFailedException::make($tool->name);
            }
        };

        $service = app()->makeWith(AgentToolService::class, ['caller' => $caller]);

        $result = $service->invoke($tool->workspace_id, $tool->name, []);

        $this->assertStringContainsString($tool->name, $result);
    }

    #[Test]
    public function un_outil_desactive_n_est_pas_propose_a_claude(): void
    {
        AgentTool::factory()->create(['enabled' => true, 'name' => 'actif']);
        $disabled = AgentTool::factory()->create(['enabled' => false, 'name' => 'inactif']);

        $service = app(AgentToolService::class);

        $definitions = $service->availableToolDefinitions($disabled->workspace_id);

        $this->assertSame([], $definitions);
    }
}
