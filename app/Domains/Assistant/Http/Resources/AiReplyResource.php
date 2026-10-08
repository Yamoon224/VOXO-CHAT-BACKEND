<?php

namespace App\Domains\Assistant\Http\Resources;

use App\Domains\Assistant\DTOs\AiReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiReply */
class AiReplyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'content' => $this->content,
            'citations' => $this->citations,
            'confidence' => $this->confidence,
        ];
    }
}
