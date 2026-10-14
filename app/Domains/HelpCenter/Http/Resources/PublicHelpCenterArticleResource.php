<?php

namespace App\Domains\HelpCenter\Http\Resources;

use App\Models\HelpCenterArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue publique : jamais le statut (toujours publié ici de toute façon) — même
 * raison que `PublicWidgetSettingsResource` (domaine `Widget`).
 *
 * @mixin HelpCenterArticle
 */
class PublicHelpCenterArticleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'locale' => $this->locale,
            'body' => $this->body,
            'meta_description' => $this->meta_description,
            'category' => $this->whenLoaded('category', fn () => $this->category === null ? null : [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
