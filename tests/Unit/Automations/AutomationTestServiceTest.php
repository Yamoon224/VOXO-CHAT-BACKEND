<?php

namespace Tests\Unit\Automations;

use App\Domains\Automations\Services\AutomationTestService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryAutomationScenarioRepository;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class AutomationTestServiceTest extends TestCase
{
    #[Test]
    public function un_message_qui_ne_correspond_pas_renvoie_les_conditions_non_satisfaites(): void
    {
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'conditions' => [['field' => 'message', 'operator' => 'contains', 'value' => 'facture']],
            'steps' => [['type' => 'send_message', 'config' => ['body' => 'Bonjour !']]],
        ]);
        $service = new AutomationTestService(new InMemoryAutomationScenarioRepository($scenario), new InMemoryMembershipRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $result = $service->test($scope, $scenario->id, 'Bonjour, une question générale.', null);

        $this->assertFalse($result['conditions_matched']);
    }

    #[Test]
    public function la_simulation_s_arrete_a_la_premiere_question(): void
    {
        $scenario = ModelFactory::automationScenario([
            'workspace_id' => 'workspace-1',
            'conditions' => [],
            'steps' => [
                ['type' => 'send_message', 'config' => ['body' => 'Bonjour !']],
                ['type' => 'ask_question', 'config' => ['body' => 'Votre e-mail ?', 'attribute_key' => 'email']],
                ['type' => 'send_message', 'config' => ['body' => 'Jamais atteint.']],
            ],
        ]);
        $service = new AutomationTestService(new InMemoryAutomationScenarioRepository($scenario), new InMemoryMembershipRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $result = $service->test($scope, $scenario->id, 'Bonjour.', null);

        $this->assertTrue($result['conditions_matched']);
        $this->assertCount(2, $result['steps']);
        $this->assertSame('ask_question', $result['steps'][1]['type']);
    }
}
