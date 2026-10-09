<?php

namespace App\Domains\Platform\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workspace */
class PlatformWorkspaceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at?->toIso8601String(),
            'member_count' => $this->members_count,
            'plan_slug' => $subscription?->plan->slug,
            'plan_name' => $subscription?->plan->name,
            'subscription_status' => $subscription?->status->value,
            'current_period_end' => $subscription?->current_period_end?->toIso8601String(),
        ];
    }
}
