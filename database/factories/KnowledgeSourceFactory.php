<?php

namespace Database\Factories;

use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Models\KnowledgeSource;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeSource> */
class KnowledgeSourceFactory extends Factory
{
    protected $model = KnowledgeSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => KnowledgeSourceType::Upload,
            'name' => $this->faker->words(3, true),
            'recrawl_frequency' => RecrawlFrequency::Manual,
        ];
    }

    public function website(string $url = 'https://example.test', ?string $sitemapUrl = null): static
    {
        return $this->state(fn () => [
            'type' => KnowledgeSourceType::Website,
            'website_url' => $url,
            'website_sitemap_url' => $sitemapUrl,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn () => ['type' => KnowledgeSourceType::Manual]);
    }
}
