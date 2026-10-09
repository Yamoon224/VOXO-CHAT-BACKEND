<?php

namespace App\Models;

use App\Domains\Billing\Enums\AiCreditTransactionType;
use Database\Factories\AiCreditTransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ligne du grand livre de crédits IA (section 2.13) : dotation, consommation
 * ou recharge. Le solde d'un espace est la somme de ses lignes, jamais un
 * compteur séparé qui pourrait diverger.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $subscription_id
 * @property AiCreditTransactionType $type
 * @property int $amount
 * @property string|null $reason
 * @property string|null $reference_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiCreditTransaction extends Model
{
    /** @use HasFactory<AiCreditTransactionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'subscription_id', 'type', 'amount', 'reason', 'reference_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AiCreditTransactionType::class,
            'amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
