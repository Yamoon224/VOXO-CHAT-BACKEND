<?php

namespace App\Models;

use Database\Factories\WebhookSubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Abonnement webhook sortant (lot 6, section 2.14) : une URL, un secret de
 * signature, les événements souscrits — voir `WebhookDispatchService`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $url
 * @property string $secret
 * @property list<string> $event_types
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookSubscription extends Model
{
    /** @use HasFactory<WebhookSubscriptionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'url', 'secret', 'event_types', 'enabled'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['event_types' => 'array', 'enabled' => 'boolean'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function subscribesTo(string $eventType): bool
    {
        return in_array($eventType, $this->event_types, true);
    }
}
