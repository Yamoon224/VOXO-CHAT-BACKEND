<?php

namespace App\Domains\Knowledge\Http\Resources;

use App\Models\KnowledgeSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeSource */
class KnowledgeSourceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'name' => $this->name,
            'website_url' => $this->website_url,
            'website_sitemap_url' => $this->website_sitemap_url,
            'recrawl_frequency' => $this->recrawl_frequency->value,
            'last_crawled_at' => $this->last_crawled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
