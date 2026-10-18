<?php

namespace App\Domains\Integrations\Http\Resources;

use App\Models\WebhookSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookSubscription
 *
 * Le secret est toujours rendu en clair : contrairement à une clé d'API,
 * l'abonné en a besoin en continu pour vérifier `X-Voxo-Signature` sur
 * chaque livraison (voir le commentaire de la migration).
 */
class WebhookSubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'secret' => $this->secret,
            'event_types' => $this->event_types,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
        ];
    }
}
