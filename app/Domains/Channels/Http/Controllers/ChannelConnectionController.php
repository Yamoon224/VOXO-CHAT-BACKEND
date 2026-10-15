<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Http\Requests\UpdateChannelConnectionRequest;
use App\Domains\Channels\Http\Resources\ChannelConnectionResource;
use App\Domains\Channels\Services\ChannelConnectionService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChannelConnectionController extends Controller
{
    public function __construct(private readonly ChannelConnectionService $connections) {}

    public function show(Request $request, string $channel): ChannelConnectionResource
    {
        return new ChannelConnectionResource($this->connections->current(WorkspaceScope::fromRequest($request), $channel));
    }

    public function update(UpdateChannelConnectionRequest $request, string $channel): ChannelConnectionResource
    {
        return new ChannelConnectionResource(
            $this->connections->update(WorkspaceScope::fromRequest($request), $channel, $request->validated()),
        );
    }
}
