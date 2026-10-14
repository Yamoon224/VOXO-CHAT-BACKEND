<?php

namespace Database\Factories;

use App\Models\ChannelEmailSettings;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChannelEmailSettings> */
class ChannelEmailSettingsFactory extends Factory
{
    protected $model = ChannelEmailSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'enabled' => false,
            'reply_from_email' => null,
            'reply_from_name' => null,
        ];
    }
}
