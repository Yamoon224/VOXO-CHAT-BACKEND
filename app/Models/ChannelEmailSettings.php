<?php

namespace App\Models;

use Database\Factories\ChannelEmailSettingsFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Réglages du canal e-mail d'un espace de travail (section 2.5). Une ligne
 * par espace, créée à la demande — même mécanique que `WidgetSettings`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property bool $enabled
 * @property string|null $reply_from_email
 * @property string|null $reply_from_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChannelEmailSettings extends Model
{
    /** @use HasFactory<ChannelEmailSettingsFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'enabled', 'reply_from_email', 'reply_from_name'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
