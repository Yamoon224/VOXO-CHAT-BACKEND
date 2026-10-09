<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Models\Invoice;
use Illuminate\Support\Collection;

final class InvoiceService
{
    public function __construct(private readonly InvoiceRepositoryContract $invoices) {}

    /** @return Collection<int, Invoice> */
    public function listForWorkspace(string $workspaceId): Collection
    {
        return $this->invoices->forWorkspace($workspaceId);
    }
}
