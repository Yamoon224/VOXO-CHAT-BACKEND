<?php

namespace App\Domains\Conversations\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'visitor_name' => $this->visitor_name,
            'visitor_email' => $this->visitor_email,
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser === null ? null : [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'subject' => $this->subject,
            'summary' => $this->summary,
            'sentiment' => $this->sentiment,
            'needs_human' => $this->needs_human,
            'rating' => $this->rating,
            'rating_comment' => $this->rating_comment,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
