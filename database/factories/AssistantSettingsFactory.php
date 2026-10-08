<?php

namespace Database\Factories;

use App\Models\AssistantSettings;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssistantSettings> */
class AssistantSettingsFactory extends Factory
{
    protected $model = AssistantSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'enabled' => true,
            'confidence_threshold' => 0.60,
        ];
    }
}
