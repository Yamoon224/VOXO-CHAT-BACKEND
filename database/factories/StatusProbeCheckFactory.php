<?php

namespace Database\Factories;

use App\Domains\StatusPage\Enums\ProbeStatus;
use App\Models\StatusProbe;
use App\Models\StatusProbeCheck;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusProbeCheck> */
class StatusProbeCheckFactory extends Factory
{
    protected $model = StatusProbeCheck::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $workspace = Workspace::factory()->create();

        return [
            'workspace_id' => $workspace->id,
            'probe_id' => StatusProbe::factory()->create(['workspace_id' => $workspace->id])->id,
            'status' => ProbeStatus::Up,
            'response_time_ms' => 120,
            'checked_at' => now(),
        ];
    }
}
