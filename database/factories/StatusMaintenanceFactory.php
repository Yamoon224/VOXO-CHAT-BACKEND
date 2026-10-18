<?php

namespace Database\Factories;

use App\Domains\StatusPage\Enums\MaintenanceStatus;
use App\Models\StatusMaintenance;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusMaintenance> */
class StatusMaintenanceFactory extends Factory
{
    protected $model = StatusMaintenance::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->unique()->sentence(3),
            'description' => null,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_start' => now()->addDay(),
            'scheduled_end' => now()->addDay()->addHours(2),
        ];
    }
}
