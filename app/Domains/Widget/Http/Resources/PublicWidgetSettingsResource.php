<?php

namespace App\Domains\Widget\Http\Resources;

use App\Models\WidgetSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Vue publique : ce que le widget a besoin de savoir pour s'afficher, rien de plus. @mixin WidgetSettings */
class PublicWidgetSettingsResource extends JsonResource
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
            'is_open_now' => $this->isOpenAt(now()),
            'offline_message' => $this->offline_message,
        ];
    }
}
