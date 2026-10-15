<?php

namespace App\Models;

use Database\Factories\InboxViewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sous-boîte (section 2.4) : un filtre de la boîte de réception, nommé et
 * enregistré — pas une vue matérialisée, juste les mêmes filtres que
 * `GET /workspace/conversations` appliqués en un clic.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property int $position
 * @property array<string, mixed> $filters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InboxView extends Model
{
    /** @use HasFactory<InboxViewFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'position', 'filters'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
