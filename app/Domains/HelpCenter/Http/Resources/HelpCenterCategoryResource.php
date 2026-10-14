<?php

namespace App\Domains\HelpCenter\Http\Resources;

use App\Models\HelpCenterCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Même forme côté agent et côté public : aucun champ interne à cacher.
 *
 * @mixin HelpCenterCategory
 */
class HelpCenterCategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'locale' => $this->locale,
            'position' => $this->position,
        ];
    }
}
