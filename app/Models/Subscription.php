<?php

namespace App\Models;

use App\Domains\Billing\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Abonnement d'un espace de travail à un palier (section 2.13). Une ligne par
 * espace, créée à sa provision (essai gratuit, sans carte bancaire).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property Carbon|null $canceled_at
 * @property string|null $payment_provider
 * @property string|null $payment_provider_customer_id
 * @property string|null $payment_provider_subscription_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'plan_id', 'status', 'trial_ends_at', 'current_period_start', 'current_period_end',
        'canceled_at', 'payment_provider', 'payment_provider_customer_id', 'payment_provider_subscription_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<AiCreditTransaction, $this> */
    public function aiCreditTransactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isTrialExpired(): bool
    {
        return $this->status === SubscriptionStatus::Trialing
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function isPeriodDue(): bool
    {
        return $this->current_period_end !== null && $this->current_period_end->isPast();
    }
}
