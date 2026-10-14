<?php

namespace Database\Factories;

use App\Models\HelpCenterCategory;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<HelpCenterCategory> */
class HelpCenterCategoryFactory extends Factory
{
    protected $model = HelpCenterCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = 'Catégorie '.Str::random(6);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'locale' => 'fr',
            'position' => 0,
        ];
    }
}
