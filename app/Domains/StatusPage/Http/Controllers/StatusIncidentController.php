<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Http\Requests\AddStatusIncidentUpdateRequest;
use App\Domains\StatusPage\Http\Requests\StoreStatusIncidentRequest;
use App\Domains\StatusPage\Http\Resources\StatusIncidentResource;
use App\Domains\StatusPage\Http\Resources\StatusIncidentUpdateResource;
use App\Domains\StatusPage\Services\StatusIncidentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StatusIncidentController extends Controller
{
    public function __construct(private readonly StatusIncidentService $incidents) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return StatusIncidentResource::collection($this->incidents->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreStatusIncidentRequest $request): JsonResponse
    {
        $incident = $this->incidents->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new StatusIncidentResource($incident))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $incident): Response
    {
        $this->incidents->delete(WorkspaceScope::fromRequest($request), $incident);

        return response()->noContent();
    }

    public function updates(Request $request, string $incident): AnonymousResourceCollection
    {
        return StatusIncidentUpdateResource::collection(
            $this->incidents->updatesFor(WorkspaceScope::fromRequest($request), $incident),
        );
    }

    public function addUpdate(AddStatusIncidentUpdateRequest $request, string $incident): StatusIncidentResource
    {
        return new StatusIncidentResource(
            $this->incidents->addUpdate(WorkspaceScope::fromRequest($request), $incident, $request->toAttributes()),
        );
    }
}
