<?php

namespace App\Domains\Assistant\Http\Controllers;

use App\Domains\Assistant\Http\Requests\UpdateAssistantSettingsRequest;
use App\Domains\Assistant\Http\Resources\AssistantSettingsResource;
use App\Domains\Assistant\Services\AssistantSettingsService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssistantSettingsController extends Controller
{
    public function __construct(private readonly AssistantSettingsService $settings) {}

    public function show(Request $request): AssistantSettingsResource
    {
        return new AssistantSettingsResource($this->settings->current(WorkspaceScope::fromRequest($request)));
    }

    public function update(UpdateAssistantSettingsRequest $request): AssistantSettingsResource
    {
        return new AssistantSettingsResource($this->settings->update(WorkspaceScope::fromRequest($request), $request->validated()));
    }
}
