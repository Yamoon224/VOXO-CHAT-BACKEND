<?php

namespace Database\Factories;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use App\Models\HelpCenterArticle;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<HelpCenterArticle> */
class HelpCenterArticleFactory extends Factory
{
    protected $model = HelpCenterArticle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = 'Article '.Str::random(6);

        return [
            'workspace_id' => Workspace::factory(),
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'locale' => 'fr',
            'body' => 'Contenu de test.',
            'status' => ArticleStatus::Draft,
            'meta_description' => null,
            'position' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ArticleStatus::Published, 'published_at' => now()]);
    }
}
