<?php

namespace Database\Factories;

use App\Domains\StatusPage\Enums\IncidentStatus;
use App\Models\StatusIncident;
use App\Models\StatusIncidentUpdate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusIncidentUpdate> */
class StatusIncidentUpdateFactory extends Factory
{
    protected $model = StatusIncidentUpdate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $workspace = Workspace::factory()->create();

        return [
            'workspace_id' => $workspace->id,
            'incident_id' => StatusIncident::factory()->create(['workspace_id' => $workspace->id])->id,
            'status' => IncidentStatus::Investigating,
            'body' => $this->faker->sentence(),
        ];
    }
}
