<?php

namespace App\Domains\Billing\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'hosted_invoice_url' => $this->hosted_invoice_url,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
