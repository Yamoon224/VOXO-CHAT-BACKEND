<?php

namespace App\Domains\Analytics\Http\Controllers;

use App\Domains\Analytics\Http\Requests\AnalyticsOverviewRequest;
use App\Domains\Analytics\Services\AnalyticsExportService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AnalyticsExportController extends Controller
{
    public function __construct(private readonly AnalyticsExportService $exports) {}

    public function index(AnalyticsOverviewRequest $request): Response
    {
        $validated = $request->validated();
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now();
        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : $to->copy()->subDays(30);

        $csv = $this->exports->export(WorkspaceScope::fromRequest($request), $from, $to);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics.csv"',
        ]);
    }
}
