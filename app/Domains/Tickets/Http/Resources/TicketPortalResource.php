<?php

namespace App\Domains\Tickets\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Vue client du portail : jamais l'affectation ni les notes internes, qui ne la concernent pas. */
class TicketPortalResource extends JsonResource
{
    /**
     * @mixin Ticket
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $conversation = $this->conversation;

        return [
            'id' => $this->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status->value,
            'status_label' => $conversation->status->label(),
            'priority_label' => $this->priority->label(),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
