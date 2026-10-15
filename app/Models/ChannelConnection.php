<?php

namespace App\Models;

use Database\Factories\ChannelConnectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Connexion d'un espace de travail à un canal de messagerie (lot 5 :
 * whatsapp, messenger, instagram, telegram, sms) — une ligne par couple
 * (espace, canal), créée à la demande, même mécanique que `ChannelEmailSettings`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $channel
 * @property bool $enabled
 * @property array<string, mixed> $credentials
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChannelConnection extends Model
{
    /** @use HasFactory<ChannelConnectionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'channel', 'enabled', 'credentials'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'credentials' => 'array'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
