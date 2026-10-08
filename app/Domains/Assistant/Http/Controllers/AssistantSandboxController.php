<?php

namespace App\Domains\Assistant\Http\Controllers;

use App\Domains\Assistant\Http\Requests\SandboxRequest;
use App\Domains\Assistant\Http\Resources\AiReplyResource;
use App\Domains\Assistant\Services\AssistantService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;

class AssistantSandboxController extends Controller
{
    public function __construct(private readonly AssistantService $assistant) {}

    public function store(SandboxRequest $request): AiReplyResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new AiReplyResource($this->assistant->sandboxRespond($scope->workspaceId, $request->string('message')->toString()));
    }
}
