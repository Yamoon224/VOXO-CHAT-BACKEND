<?php

namespace App\Domains\Billing\Http\Controllers;

use App\Domains\Billing\Http\Resources\InvoiceResource;
use App\Domains\Billing\Services\InvoiceService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return InvoiceResource::collection(
            $this->invoices->listForWorkspace(WorkspaceScope::fromRequest($request)->workspaceId),
        );
    }
}
