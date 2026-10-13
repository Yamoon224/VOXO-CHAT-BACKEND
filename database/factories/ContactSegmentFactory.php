<?php

namespace Database\Factories;

use App\Models\ContactSegment;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactSegment> */
class ContactSegmentFactory extends Factory
{
    protected $model = ContactSegment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'criteria' => [['field' => 'email', 'operator' => 'is_set', 'value' => null]],
        ];
    }
}
