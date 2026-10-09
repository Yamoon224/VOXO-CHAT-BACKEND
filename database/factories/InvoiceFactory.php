<?php

namespace Database\Factories;

use App\Domains\Payments\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();

        return [
            'workspace_id' => $subscription->workspace_id,
            'subscription_id' => $subscription->id,
            'amount_cents' => 1900,
            'currency' => 'EUR',
            'status' => InvoiceStatus::Paid,
            'issued_at' => now(),
            'paid_at' => now(),
        ];
    }
}
