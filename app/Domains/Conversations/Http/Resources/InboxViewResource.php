<?php

namespace App\Domains\Conversations\Http\Resources;

use App\Models\InboxView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InboxView */
class InboxViewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'filters' => $this->filters,
        ];
    }
}
