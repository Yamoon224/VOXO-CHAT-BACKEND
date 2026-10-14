<?php

namespace App\Domains\Tickets\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Vue agent : priorité, échéance SLA, et l'essentiel de la conversation qu'elle habille. */
class TicketResource extends JsonResource
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
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'due_at' => $this->due_at?->toIso8601String(),
            'is_overdue' => $this->due_at !== null && $this->due_at->isPast() && $conversation->status->value !== 'resolved',
            'created_at' => $this->created_at?->toIso8601String(),
            'contact' => $this->whenLoaded('contact', fn () => $this->contact === null ? null : [
                'id' => $this->contact->id,
                'name' => $this->contact->name,
                'email' => $this->contact->email,
            ]),
            'conversation' => [
                'id' => $conversation->id,
                'channel' => $conversation->channel->value,
                'channel_label' => $conversation->channel->label(),
                'status' => $conversation->status->value,
                'status_label' => $conversation->status->label(),
                'subject' => $conversation->subject,
                'visitor_name' => $conversation->visitor_name,
                'visitor_email' => $conversation->visitor_email,
                'assigned_user' => $conversation->relationLoaded('assignedUser') && $conversation->assignedUser !== null ? [
                    'id' => $conversation->assignedUser->id,
                    'name' => $conversation->assignedUser->name,
                ] : null,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ],
        ];
    }
}
