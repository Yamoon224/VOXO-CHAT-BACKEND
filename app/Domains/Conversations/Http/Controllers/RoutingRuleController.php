<?php

namespace App\Domains\Conversations\Http\Controllers;

use App\Domains\Conversations\Http\Requests\StoreRoutingRuleRequest;
use App\Domains\Conversations\Http\Requests\UpdateRoutingRuleRequest;
use App\Domains\Conversations\Http\Resources\RoutingRuleResource;
use App\Domains\Conversations\Services\RoutingRuleService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RoutingRuleController extends Controller
{
    public function __construct(private readonly RoutingRuleService $rules) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return RoutingRuleResource::collection($this->rules->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreRoutingRuleRequest $request): JsonResponse
    {
        $rule = $this->rules->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new RoutingRuleResource($rule))->response()->setStatusCode(201);
    }

    public function update(UpdateRoutingRuleRequest $request, string $rule): RoutingRuleResource
    {
        return new RoutingRuleResource(
            $this->rules->update(WorkspaceScope::fromRequest($request), $rule, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $rule): Response
    {
        $this->rules->delete(WorkspaceScope::fromRequest($request), $rule);

        return response()->noContent();
    }
}
