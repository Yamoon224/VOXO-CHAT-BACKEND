<?php

namespace Database\Factories;

use App\Domains\Billing\Enums\AiCreditTransactionType;
use App\Models\AiCreditTransaction;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiCreditTransaction> */
class AiCreditTransactionFactory extends Factory
{
    protected $model = AiCreditTransaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();

        return [
            'workspace_id' => $subscription->workspace_id,
            'subscription_id' => $subscription->id,
            'type' => AiCreditTransactionType::Grant,
            'amount' => 1000,
        ];
    }

    public function usage(int $amount = -1): static
    {
        return $this->state(fn () => ['type' => AiCreditTransactionType::Usage, 'amount' => $amount]);
    }
}
