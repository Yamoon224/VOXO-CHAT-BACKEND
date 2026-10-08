<?php

namespace App\Models;

use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use Database\Factories\KnowledgeSourceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Source de connaissance : un import de fichiers, un site web exploré, ou le
 * conteneur des entrées manuelles d'un espace. Chaque document indexé s'y
 * rattache.
 *
 * @property string $id
 * @property string $workspace_id
 * @property KnowledgeSourceType $type
 * @property string $name
 * @property string|null $website_url
 * @property string|null $website_sitemap_url
 * @property RecrawlFrequency $recrawl_frequency
 * @property Carbon|null $last_crawled_at
 * @property string|null $created_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KnowledgeSource extends Model
{
    /** @use HasFactory<KnowledgeSourceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'type', 'name', 'website_url', 'website_sitemap_url',
        'recrawl_frequency', 'last_crawled_at', 'created_by_user_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => KnowledgeSourceType::class,
            'recrawl_frequency' => RecrawlFrequency::class,
            'last_crawled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<KnowledgeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'source_id');
    }

    public function isDueForRecrawl(): bool
    {
        $hours = $this->recrawl_frequency->intervalInHours();

        if ($hours === null) {
            return false;
        }

        return $this->last_crawled_at === null || $this->last_crawled_at->lt(now()->subHours($hours));
    }
}
