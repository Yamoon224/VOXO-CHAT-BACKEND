<?php

namespace App\Domains\Tickets\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue du client sur les messages de son ticket : jamais une note interne — même raison que `PublicMessageResource` (domaine `Widget`).
 *
 * @mixin Message
 */
class TicketPortalMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type->value,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
