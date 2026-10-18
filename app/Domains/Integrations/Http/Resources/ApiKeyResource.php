<?php

namespace App\Domains\Integrations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Enveloppe un `ApiKeyInfo` (domaine `Auth`) sans jamais nommer ce DTO — voir
 * `ApiKeyService`. Les annotations ci-dessous ne décrivent que la forme
 * attendue, pour l'analyse statique.
 *
 * @property string $id
 * @property string $name
 * @property string $createdByName
 * @property Carbon|null $lastUsedAt
 * @property Carbon $createdAt
 */
class ApiKeyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_by_name' => $this->createdByName,
            'last_used_at' => $this->lastUsedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
