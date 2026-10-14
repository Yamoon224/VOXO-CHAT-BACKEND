<?php

namespace App\Models;

use Database\Factories\HelpCenterCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Catégorie du centre d'aide public (section 2.10) : un simple regroupement,
 * aucun statut propre — seuls ses articles sont publiés ou non.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string $slug
 * @property string $locale
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HelpCenterCategory extends Model
{
    /** @use HasFactory<HelpCenterCategoryFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'slug', 'locale', 'position'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<HelpCenterArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(HelpCenterArticle::class, 'category_id');
    }
}
