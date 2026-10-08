<?php

namespace App\Models;

use App\Domains\Widget\Enums\WidgetPosition;
use Database\Factories\WidgetSettingsFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Réglages d'apparence et d'horaires du widget d'un espace de travail
 * (section 2.3). Une ligne par espace, créée à la demande.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $primary_color
 * @property string|null $logo_url
 * @property WidgetPosition $position
 * @property string|null $welcome_message
 * @property string $language
 * @property array<int, array{day: int, opens_at: string, closes_at: string}>|null $business_hours
 * @property string|null $offline_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WidgetSettings extends Model
{
    /** @use HasFactory<WidgetSettingsFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'primary_color', 'logo_url', 'position', 'welcome_message',
        'language', 'business_hours', 'offline_message',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => WidgetPosition::class,
            'business_hours' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Ouvert en continu si aucun horaire n'est réglé : un espace qui n'a pas
     * encore configuré ses horaires ne doit pas paraître fermé par défaut.
     */
    public function isOpenAt(Carbon $moment): bool
    {
        if ($this->business_hours === null || $this->business_hours === []) {
            return true;
        }

        $day = (int) $moment->dayOfWeek;
        $time = $moment->format('H:i');

        foreach ($this->business_hours as $slot) {
            if ((int) $slot['day'] === $day && $time >= $slot['opens_at'] && $time < $slot['closes_at']) {
                return true;
            }
        }

        return false;
    }
}
