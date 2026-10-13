<?php

namespace App\Domains\Contacts\Http\Resources;

use App\Models\ContactNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContactNote */
class ContactNoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author_name' => $this->author?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
