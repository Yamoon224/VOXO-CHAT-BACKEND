<?php

namespace App\Models;

use Database\Factories\AssistantSettingsFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Réglages de l'agent IA d'un espace de travail (section 2.2) : ton et
 * consignes, seuil de confiance avant escalade, activation. Une ligne par
 * espace, créée à la demande.
 *
 * @property string $id
 * @property string $workspace_id
 * @property bool $enabled
 * @property string|null $tone_instructions
 * @property float $confidence_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AssistantSettings extends Model
{
    /** @use HasFactory<AssistantSettingsFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'enabled', 'tone_instructions', 'confidence_threshold'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'confidence_threshold' => 'float',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
