<?php

namespace Database\Factories;

use App\Models\StatusSubscriber;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatusSubscriber> */
class StatusSubscriberFactory extends Factory
{
    protected $model = StatusSubscriber::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
