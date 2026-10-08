<?php

namespace App\Domains\Widget\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Widget\Http\Requests\UpdateWidgetSettingsRequest;
use App\Domains\Widget\Http\Resources\WidgetSettingsResource;
use App\Domains\Widget\Services\WidgetSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetSettingsController extends Controller
{
    public function __construct(private readonly WidgetSettingsService $settings) {}

    public function show(Request $request): WidgetSettingsResource
    {
        return new WidgetSettingsResource($this->settings->current(WorkspaceScope::fromRequest($request)));
    }

    public function update(UpdateWidgetSettingsRequest $request): WidgetSettingsResource
    {
        return new WidgetSettingsResource($this->settings->update(WorkspaceScope::fromRequest($request), $request->validated()));
    }

    public function script(Request $request): JsonResponse
    {
        return response()->json(['data' => ['script' => $this->settings->script(WorkspaceScope::fromRequest($request))]]);
    }
}
