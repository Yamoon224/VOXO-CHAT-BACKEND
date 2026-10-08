<?php

namespace App\Domains\Assistant\Http\Resources;

use App\Models\AssistantSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssistantSettings */
class AssistantSettingsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'enabled' => $this->enabled,
            'tone_instructions' => $this->tone_instructions,
            'confidence_threshold' => $this->confidence_threshold,
        ];
    }
}
