<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Models\Invoice;
use Illuminate\Support\Collection;

final class InMemoryInvoiceRepository implements InvoiceRepositoryContract
{
    /** @var array<string, Invoice> */
    private array $invoices = [];

    public function __construct(Invoice ...$invoices)
    {
        foreach ($invoices as $invoice) {
            $this->invoices[$invoice->id] = $invoice;
        }
    }

    /** @return Collection<int, Invoice> */
    public function forWorkspace(string $workspaceId): Collection
    {
        return (new Collection($this->invoices))
            ->filter(fn (Invoice $invoice) => $invoice->workspace_id === $workspaceId)
            ->sortByDesc('issued_at')
            ->values();
    }

    public function findByProviderInvoiceId(string $providerInvoiceId): ?Invoice
    {
        foreach ($this->invoices as $invoice) {
            if ($invoice->payment_provider_invoice_id === $providerInvoiceId) {
                return $invoice;
            }
        }

        return null;
    }

    public function create(array $attributes): Invoice
    {
        $invoice = ModelFactory::invoice($attributes);

        return $this->invoices[$invoice->id] = $invoice;
    }

    public function update(Invoice $invoice, array $attributes): Invoice
    {
        $invoice->setRawAttributes($attributes + $invoice->getAttributes(), true);

        return $invoice;
    }
}
