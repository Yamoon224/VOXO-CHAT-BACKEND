<?php

namespace App\Domains\Billing\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
class PlanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'billing_interval' => $this->billing_interval->value,
            'max_seats' => $this->max_seats,
            'max_contacts' => $this->max_contacts,
            'ai_credits_per_month' => $this->ai_credits_per_month,
            'max_knowledge_documents' => $this->max_knowledge_documents,
            'is_custom' => $this->is_custom,
            'sort_order' => $this->sort_order,
        ];
    }
}
