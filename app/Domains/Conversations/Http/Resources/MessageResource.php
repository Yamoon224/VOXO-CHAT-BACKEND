<?php

namespace App\Domains\Conversations\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Vue d'équipe : inclut les notes internes. @mixin Message */
class MessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type->value,
            'sender_label' => $this->sender_type->label(),
            'sender_user' => $this->senderUser === null ? null : ['id' => $this->senderUser->id, 'name' => $this->senderUser->name],
            'visibility' => $this->visibility->value,
            'body' => $this->body,
            'citations' => $this->citations ?? [],
            'attachment_filename' => $this->attachment_filename,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
