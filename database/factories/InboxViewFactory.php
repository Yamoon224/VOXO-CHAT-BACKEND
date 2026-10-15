<?php

namespace Database\Factories;

use App\Models\InboxView;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InboxView> */
class InboxViewFactory extends Factory
{
    protected $model = InboxView::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Vue de test',
            'position' => 0,
            'filters' => [],
        ];
    }
}
