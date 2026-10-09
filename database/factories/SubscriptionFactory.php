<?php

namespace Database\Factories;

use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => SubscriptionStatus::Active, 'trial_ends_at' => null]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn () => ['trial_ends_at' => now()->subDay()]);
    }

    public function periodDue(): static
    {
        return $this->state(fn () => ['current_period_end' => now()->subDay()]);
    }
}
