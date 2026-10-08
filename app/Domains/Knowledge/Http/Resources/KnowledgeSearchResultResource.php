<?php

namespace App\Domains\Knowledge\Http\Resources;

use App\Domains\Knowledge\DTOs\KnowledgeSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeSearchResult */
class KnowledgeSearchResultResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->documentId,
            'document_title' => $this->documentTitle,
            'chunk_content' => $this->chunkContent,
            'score' => $this->score,
            'citation_url' => $this->citationUrl,
        ];
    }
}
