<?php

namespace Database\Factories;

use App\Domains\StatusPage\Enums\ProbeStatus;
use App\Models\StatusProbe;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusProbe> */
class StatusProbeFactory extends Factory
{
    protected $model = StatusProbe::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'url' => 'https://exemple.fr/health',
            'check_interval_minutes' => 5,
            'status' => ProbeStatus::Unknown,
            'consecutive_failures' => 0,
        ];
    }
}
