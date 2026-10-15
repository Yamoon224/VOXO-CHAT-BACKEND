<?php

namespace Database\Factories;

use App\Domains\Automations\Enums\AutomationRunStatus;
use App\Models\AutomationRun;
use App\Models\AutomationScenario;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AutomationRun> */
class AutomationRunFactory extends Factory
{
    protected $model = AutomationRun::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'conversation_id' => Conversation::factory(),
            'scenario_id' => AutomationScenario::factory(),
            'current_step' => 0,
            'status' => AutomationRunStatus::Running,
        ];
    }
}
