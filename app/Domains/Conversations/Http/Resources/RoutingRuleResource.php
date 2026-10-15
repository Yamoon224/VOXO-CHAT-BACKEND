<?php

namespace App\Domains\Conversations\Http\Resources;

use App\Models\RoutingRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoutingRule */
class RoutingRuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'conditions' => $this->conditions,
            'action_type' => $this->action_type->value,
            'action_type_label' => $this->action_type->label(),
            'assignee_user_ids' => $this->assignee_user_ids,
        ];
    }
}
