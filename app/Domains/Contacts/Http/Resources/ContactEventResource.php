<?php

namespace App\Domains\Contacts\Http\Resources;

use App\Models\ContactEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContactEvent */
class ContactEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'payload' => $this->payload ?? [],
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
