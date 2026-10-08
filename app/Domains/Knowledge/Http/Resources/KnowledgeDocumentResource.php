<?php

namespace App\Domains\Knowledge\Http\Resources;

use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeDocument */
class KnowledgeDocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_id' => $this->source_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'origin_url' => $this->origin_url,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_message' => $this->status_message,
            'chunk_count' => $this->chunk_count,
            'indexed_at' => $this->indexed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
