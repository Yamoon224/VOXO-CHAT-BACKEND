<?php

namespace App\Domains\Billing\Http\Requests;

use App\Domains\Billing\Enums\BillingInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('plans', 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', Rule::in(BillingInterval::values())],
            'max_seats' => ['nullable', 'integer', 'min:0'],
            'max_contacts' => ['nullable', 'integer', 'min:0'],
            'ai_credits_per_month' => ['nullable', 'integer', 'min:0'],
            'max_knowledge_documents' => ['nullable', 'integer', 'min:0'],
            'is_custom' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'provider_price_id' => ['nullable', 'string', 'max:255'],
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
