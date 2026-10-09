<?php

namespace Database\Factories;

use App\Domains\Billing\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'slug' => $name,
            'name' => ucfirst($name),
            'price_cents' => 1900,
            'currency' => 'EUR',
            'billing_interval' => BillingInterval::Month,
            'max_seats' => 5,
            'max_contacts' => 2000,
            'ai_credits_per_month' => 1000,
            'max_knowledge_documents' => 200,
            'is_custom' => false,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'slug' => 'free', 'name' => 'Gratuit', 'price_cents' => 0,
            'max_seats' => 2, 'max_contacts' => 200, 'ai_credits_per_month' => 100, 'max_knowledge_documents' => 20,
            'sort_order' => 0,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn () => [
            'slug' => 'enterprise', 'name' => 'Grands comptes', 'price_cents' => null,
            'max_seats' => null, 'max_contacts' => null, 'ai_credits_per_month' => null, 'max_knowledge_documents' => null,
            'is_custom' => true, 'sort_order' => 3,
        ]);
    }
}
