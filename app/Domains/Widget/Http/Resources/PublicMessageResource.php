<?php

namespace App\Domains\Widget\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue du visiteur : jamais de note interne, jamais l'identité d'un agent
 * au-delà de son message.
 *
 * @mixin Message
 */
class PublicMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type->value,
            'body' => $this->body,
            'citations' => $this->citations ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
