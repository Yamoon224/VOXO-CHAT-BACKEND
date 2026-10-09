<?php

namespace App\Domains\Analytics\Http\Controllers;

use App\Domains\Analytics\Http\Requests\AnalyticsOverviewRequest;
use App\Domains\Analytics\Http\Resources\AnalyticsOverviewResource;
use App\Domains\Analytics\Services\AnalyticsService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function overview(AnalyticsOverviewRequest $request): AnalyticsOverviewResource
    {
        $validated = $request->validated();
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now();
        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : $to->copy()->subDays(30);

        return new AnalyticsOverviewResource(
            $this->analytics->overview(WorkspaceScope::fromRequest($request), $from, $to),
        );
    }
}
