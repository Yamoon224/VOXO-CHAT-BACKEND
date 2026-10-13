<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Http\Requests\ImportContactsRequest;
use App\Domains\Contacts\Services\ContactImportService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ContactImportController extends Controller
{
    public function __construct(private readonly ContactImportService $imports) {}

    public function store(ImportContactsRequest $request): JsonResponse
    {
        $summary = $this->imports->import(
            WorkspaceScope::fromRequest($request)->workspaceId,
            $request->file('file'),
        );

        return response()->json(['data' => $summary]);
    }
}
