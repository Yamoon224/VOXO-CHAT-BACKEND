<?php

namespace App\Domains\Assistant\Http\Controllers;

use App\Domains\Assistant\Http\Requests\StoreAgentToolRequest;
use App\Domains\Assistant\Http\Requests\UpdateAgentToolRequest;
use App\Domains\Assistant\Http\Resources\AgentToolResource;
use App\Domains\Assistant\Services\AgentToolService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AgentToolController extends Controller
{
    public function __construct(private readonly AgentToolService $tools) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AgentToolResource::collection($this->tools->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreAgentToolRequest $request): JsonResponse
    {
        $tool = $this->tools->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new AgentToolResource($tool))->response()->setStatusCode(201);
    }

    public function update(UpdateAgentToolRequest $request, string $tool): AgentToolResource
    {
        return new AgentToolResource(
            $this->tools->update(WorkspaceScope::fromRequest($request), $tool, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $tool): Response
    {
        $this->tools->delete(WorkspaceScope::fromRequest($request), $tool);

        return response()->noContent();
    }
}
