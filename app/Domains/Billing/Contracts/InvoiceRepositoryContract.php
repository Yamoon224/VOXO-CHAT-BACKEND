<?php

namespace App\Domains\Billing\Contracts;

use App\Models\Invoice;
use Illuminate\Support\Collection;

interface InvoiceRepositoryContract
{
    /** @return Collection<int, Invoice> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findByProviderInvoiceId(string $providerInvoiceId): ?Invoice;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Invoice;

    /** @param  array<string, mixed>  $attributes */
    public function update(Invoice $invoice, array $attributes): Invoice;
}
