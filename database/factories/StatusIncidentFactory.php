<?php

namespace Database\Factories;

use App\Domains\StatusPage\Enums\IncidentImpact;
use App\Domains\StatusPage\Enums\IncidentStatus;
use App\Models\StatusIncident;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusIncident> */
class StatusIncidentFactory extends Factory
{
    protected $model = StatusIncident::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->unique()->sentence(3),
            'status' => IncidentStatus::Investigating,
            'impact' => IncidentImpact::Minor,
            'started_at' => now(),
        ];
    }
}
