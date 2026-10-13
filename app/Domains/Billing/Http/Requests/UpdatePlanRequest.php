<?php

namespace App\Domains\Billing\Http\Requests;

use App\Domains\Billing\Enums\BillingInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'required', 'string', 'max:40', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($this->route('plan'))],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'billing_interval' => ['sometimes', 'required', Rule::in(BillingInterval::values())],
            'max_seats' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_contacts' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'ai_credits_per_month' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_knowledge_documents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_custom' => ['sometimes', 'required', 'boolean'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0'],
            'provider_price_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'slug' => 'identifiant',
            'name' => 'nom',
            'price_cents' => 'prix',
            'currency' => 'devise',
            'billing_interval' => 'fréquence de facturation',
            'max_seats' => 'sièges maximum',
            'max_contacts' => 'contacts maximum',
            'ai_credits_per_month' => 'crédits IA par mois',
            'max_knowledge_documents' => 'documents maximum',
            'is_custom' => 'offre sur devis',
            'is_active' => 'actif',
            'sort_order' => 'ordre d\'affichage',
            'provider_price_id' => 'identifiant de prix Stripe',
        ];
    }
}
