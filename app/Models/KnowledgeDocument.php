<?php

namespace App\Models;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use Database\Factories\KnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Unité de contenu indexable : un fichier importé, une page explorée, ou une
 * entrée question/réponse saisie à la main. Porte le statut de la chaîne
 * d'ingestion, visible par l'équipe à chaque étape.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $source_id
 * @property KnowledgeDocumentType $type
 * @property string $title
 * @property string|null $origin_url
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property string|null $disk_path
 * @property string|null $raw_content
 * @property string|null $question
 * @property string|null $answer
 * @property KnowledgeDocumentStatus $status
 * @property string|null $status_message
 * @property int $chunk_count
 * @property Carbon|null $indexed_at
 * @property string|null $created_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'source_id', 'type', 'title', 'origin_url', 'original_filename',
        'mime_type', 'disk_path', 'raw_content', 'question', 'answer', 'status',
        'status_message', 'chunk_count', 'indexed_at', 'created_by_user_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => KnowledgeDocumentType::class,
            'status' => KnowledgeDocumentStatus::class,
            'indexed_at' => 'datetime',
            'chunk_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<KnowledgeSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }

    /** @return HasMany<KnowledgeChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id')->orderBy('position');
    }

    public function isRetryable(): bool
    {
        return $this->status === KnowledgeDocumentStatus::Failed;
    }
}
