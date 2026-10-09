<?php

namespace Database\Seeders;

use App\Domains\Billing\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Grille tarifaire indicative, à ajuster plus tard sans toucher au code —
 * décision du 9 octobre 2026, section 12 du cahier des charges. Les montants
 * ci-dessous s'inspirent d'un produit comparable (Crisp) ; ce ne sont pas des
 * prix définitifs.
 *
 * `price_cents: null` et `is_custom: true` marquent l'offre sur devis : ses
 * plafonds restent `null` (illimité), et elle ne peut pas se souscrire en
 * self-service (voir `PlanRequiresSalesContactException`).
 */
class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free', 'name' => 'Gratuit', 'price_cents' => 0,
                'max_seats' => 2, 'max_contacts' => 200, 'ai_credits_per_month' => 100, 'max_knowledge_documents' => 20,
                'is_custom' => false, 'sort_order' => 0, 'provider_price_id' => null,
            ],
            [
                'slug' => 'starter', 'name' => 'Starter', 'price_cents' => 1900,
                'max_seats' => 5, 'max_contacts' => 2000, 'ai_credits_per_month' => 1000, 'max_knowledge_documents' => 200,
                'is_custom' => false, 'sort_order' => 1, 'provider_price_id' => null,
            ],
            [
                'slug' => 'business', 'name' => 'Business', 'price_cents' => 4900,
                'max_seats' => 20, 'max_contacts' => 10000, 'ai_credits_per_month' => 5000, 'max_knowledge_documents' => 1000,
                'is_custom' => false, 'sort_order' => 2, 'provider_price_id' => null,
            ],
            [
                'slug' => 'enterprise', 'name' => 'Grands comptes', 'price_cents' => null,
                'max_seats' => null, 'max_contacts' => null, 'ai_credits_per_month' => null, 'max_knowledge_documents' => null,
                'is_custom' => true, 'sort_order' => 3, 'provider_price_id' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'currency' => 'EUR', 'billing_interval' => BillingInterval::Month, 'is_active' => true],
            );
        }
    }
}
