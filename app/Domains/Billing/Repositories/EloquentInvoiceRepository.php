<?php

namespace App\Domains\Billing\Repositories;

use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Models\Invoice;
use Illuminate\Support\Collection;

final class EloquentInvoiceRepository implements InvoiceRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return Invoice::query()->where('workspace_id', $workspaceId)->latest('issued_at')->get();
    }

    public function findByProviderInvoiceId(string $providerInvoiceId): ?Invoice
    {
        return Invoice::query()->where('payment_provider_invoice_id', $providerInvoiceId)->first();
    }

    public function create(array $attributes): Invoice
    {
        return Invoice::create($attributes);
    }

    public function update(Invoice $invoice, array $attributes): Invoice
    {
        $invoice->update($attributes);

        return $invoice;
    }
}
