<?php

namespace App\Domains\Widget\Http\Resources;

use App\Models\WidgetSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue d'équipe : tous les réglages.
 *
 * @mixin WidgetSettings
 */
class WidgetSettingsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'primary_color' => $this->primary_color,
            'logo_url' => $this->logo_url,
            'position' => $this->position->value,
            'welcome_message' => $this->welcome_message,
            'language' => $this->language,
            'business_hours' => $this->business_hours,
            'offline_message' => $this->offline_message,
        ];
    }
}
