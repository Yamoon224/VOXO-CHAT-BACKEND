<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Http\Requests\UpdateChannelEmailSettingsRequest;
use App\Domains\Channels\Http\Resources\ChannelEmailSettingsResource;
use App\Domains\Channels\Services\ChannelEmailSettingsService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChannelEmailSettingsController extends Controller
{
    public function __construct(private readonly ChannelEmailSettingsService $settings) {}

    public function show(Request $request): ChannelEmailSettingsResource
    {
        return new ChannelEmailSettingsResource($this->settings->current(WorkspaceScope::fromRequest($request)));
    }

    public function update(UpdateChannelEmailSettingsRequest $request): ChannelEmailSettingsResource
    {
        return new ChannelEmailSettingsResource(
            $this->settings->update(WorkspaceScope::fromRequest($request), $request->validated()),
        );
    }
}
