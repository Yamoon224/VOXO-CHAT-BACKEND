<?php

namespace Database\Factories;

use App\Domains\Widget\Enums\WidgetPosition;
use App\Models\WidgetSettings;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WidgetSettings> */
class WidgetSettingsFactory extends Factory
{
    protected $model = WidgetSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'primary_color' => '#4F46E5',
            'position' => WidgetPosition::BottomRight,
            'welcome_message' => 'Bonjour ! Comment pouvons-nous vous aider ?',
            'language' => 'fr',
        ];
    }
}
