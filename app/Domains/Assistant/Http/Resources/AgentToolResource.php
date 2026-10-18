<?php

namespace App\Domains\Assistant\Http\Resources;

use App\Models\AgentTool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AgentTool
 *
 * Les en-têtes sont masqués à la relecture (même raison que
 * `ChannelConnectionResource`) : un en-tête `Authorization` ne doit jamais
 * repasser en clair une fois enregistré.
 */
class AgentToolResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'http_method' => $this->http_method->value,
            'url' => $this->url,
            'headers' => $this->headers === null ? null : array_map(fn (mixed $value) => $this->mask($value), $this->headers),
            'parameters' => $this->parameters,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
        ];
    }

    private function mask(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        return mb_strlen($value) <= 4 ? '••••' : '••••'.mb_substr($value, -4);
    }
}
