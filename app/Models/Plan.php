<?php

namespace App\Models;

use App\Domains\Billing\Enums\BillingInterval;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Palier tarifaire (section 2.13). Grille indicative, à ajuster sans toucher
 * au code (voir `database/seeders/PlansSeeder`) — décision du 9 octobre 2026,
 * section 12 du cahier des charges.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property int|null $price_cents
 * @property string $currency
 * @property BillingInterval $billing_interval
 * @property int|null $max_seats
 * @property int|null $max_contacts
 * @property int|null $ai_credits_per_month
 * @property int|null $max_knowledge_documents
 * @property bool $is_custom
 * @property bool $is_active
 * @property int $sort_order
 * @property string|null $provider_price_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'slug', 'name', 'price_cents', 'currency', 'billing_interval', 'max_seats', 'max_contacts',
        'ai_credits_per_month', 'max_knowledge_documents', 'is_custom', 'is_active', 'sort_order', 'provider_price_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billing_interval' => BillingInterval::class,
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimitedOn(string $quota): bool
    {
        return $this->{$quota} === null;
    }
}
