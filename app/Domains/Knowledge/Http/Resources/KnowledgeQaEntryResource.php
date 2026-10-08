<?php

namespace App\Domains\Knowledge\Http\Resources;

use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeDocument */
class KnowledgeQaEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'chunk_count' => $this->chunk_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
