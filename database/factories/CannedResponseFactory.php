<?php

namespace Database\Factories;

use App\Models\CannedResponse;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CannedResponse> */
class CannedResponseFactory extends Factory
{
    protected $model = CannedResponse::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->unique()->words(3, true),
            'body' => $this->faker->sentence(),
        ];
    }
}
