<?php

namespace App\Models;

use App\Domains\Payments\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Facture reflétée depuis le prestataire de paiement (section 2.13). Pas de
 * génération de PDF maison : `hosted_invoice_url` pointe vers la page Stripe.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $subscription_id
 * @property string|null $payment_provider_invoice_id
 * @property int $amount_cents
 * @property string $currency
 * @property InvoiceStatus $status
 * @property string|null $hosted_invoice_url
 * @property Carbon|null $issued_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'subscription_id', 'payment_provider_invoice_id', 'amount_cents', 'currency',
        'status', 'hosted_invoice_url', 'issued_at', 'paid_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
