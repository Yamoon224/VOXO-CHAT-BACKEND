<?php

namespace App\Models;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use Database\Factories\HelpCenterArticleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Article du centre d'aide public (section 2.10).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string|null $category_id
 * @property string $title
 * @property string $slug
 * @property string $locale
 * @property string $body
 * @property ArticleStatus $status
 * @property string|null $meta_description
 * @property int $position
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HelpCenterArticle extends Model
{
    /** @use HasFactory<HelpCenterArticleFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'category_id', 'title', 'slug', 'locale', 'body',
        'status', 'meta_description', 'position', 'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => ArticleStatus::class, 'published_at' => 'datetime'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<HelpCenterCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCenterCategory::class, 'category_id');
    }
}
