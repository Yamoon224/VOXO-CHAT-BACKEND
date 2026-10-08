<?php

namespace App\Models;

use Database\Factories\KnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Passage découpé d'un document, avec son vecteur d'embedding une fois
 * calculé. La recherche sémantique compare ce vecteur à celui de la requête
 * (similarité cosinus calculée en PHP, voir `EmbeddingSearchContract`).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $document_id
 * @property int $position
 * @property string $content
 * @property int $token_count
 * @property list<float>|null $embedding
 * @property string|null $embedding_model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KnowledgeChunk extends Model
{
    /** @use HasFactory<KnowledgeChunkFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'document_id', 'position', 'content', 'token_count', 'embedding', 'embedding_model',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'position' => 'integer',
            'token_count' => 'integer',
        ];
    }

    /** @return BelongsTo<KnowledgeDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
