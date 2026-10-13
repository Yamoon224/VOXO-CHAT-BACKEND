<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Services\ContactExportService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactExportController extends Controller
{
    public function __construct(private readonly ContactExportService $exports) {}

    public function index(Request $request): Response
    {
        $csv = $this->exports->export(WorkspaceScope::fromRequest($request)->workspaceId);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts.csv"',
        ]);
    }
}
