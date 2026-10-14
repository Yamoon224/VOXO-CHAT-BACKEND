<?php

namespace App\Domains\HelpCenter\Http\Resources;

use App\Models\HelpCenterArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue agent : statut et brouillon compris — jamais servie publiquement, voir
 * `PublicHelpCenterArticleResource`.
 *
 * @mixin HelpCenterArticle
 */
class HelpCenterArticleResource extends JsonResource
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
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'meta_description' => $this->meta_description,
            'position' => $this->position,
            'category' => $this->whenLoaded('category', fn () => $this->category === null ? null : [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
