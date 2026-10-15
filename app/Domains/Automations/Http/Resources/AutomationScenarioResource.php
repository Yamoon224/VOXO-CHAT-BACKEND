<?php

namespace App\Domains\Automations\Http\Resources;

use App\Models\AutomationScenario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AutomationScenario */
class AutomationScenarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'trigger_type' => $this->trigger_type->value,
            'trigger_type_label' => $this->trigger_type->label(),
            'conditions' => $this->conditions,
            'steps' => $this->steps,
        ];
    }
}
