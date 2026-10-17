<?php

namespace App\Models;

use App\Domains\Integrations\Enums\WebhookDeliveryStatus;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Trace d'une tentative de livraison d'un événement à un abonnement (lot 6,
 * section 2.14) — pour le débogage côté espace de travail, pas pour relire
 * l'historique métier.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $subscription_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property WebhookDeliveryStatus $status
 * @property int|null $response_status
 * @property Carbon|null $attempted_at
 * @property Carbon|null $created_at
 */
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'subscription_id', 'event_type', 'payload', 'status', 'response_status', 'attempted_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array', 'status' => WebhookDeliveryStatus::class, 'attempted_at' => 'datetime'];
    }

    /** @return BelongsTo<WebhookSubscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class);
    }
}
