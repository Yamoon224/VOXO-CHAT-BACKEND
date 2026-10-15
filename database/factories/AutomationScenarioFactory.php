<?php

namespace Database\Factories;

use App\Domains\Automations\Enums\AutomationStatus;
use App\Domains\Automations\Enums\AutomationTriggerType;
use App\Models\AutomationScenario;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AutomationScenario> */
class AutomationScenarioFactory extends Factory
{
    protected $model = AutomationScenario::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Scénario de test',
            'position' => 0,
            'status' => AutomationStatus::Draft,
            'trigger_type' => AutomationTriggerType::ConversationOpened,
            'conditions' => [],
            'steps' => [],
        ];
    }
}
