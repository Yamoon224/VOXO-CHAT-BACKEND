<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Http\Requests\StoreStatusProbeRequest;
use App\Domains\StatusPage\Http\Requests\UpdateStatusProbeRequest;
use App\Domains\StatusPage\Http\Resources\StatusProbeResource;
use App\Domains\StatusPage\Services\StatusProbeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StatusProbeController extends Controller
{
    public function __construct(private readonly StatusProbeService $probes) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return StatusProbeResource::collection($this->probes->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreStatusProbeRequest $request): JsonResponse
    {
        $probe = $this->probes->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new StatusProbeResource($probe))->response()->setStatusCode(201);
    }

    public function update(UpdateStatusProbeRequest $request, string $probe): StatusProbeResource
    {
        return new StatusProbeResource(
            $this->probes->update(WorkspaceScope::fromRequest($request), $probe, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $probe): Response
    {
        $this->probes->delete(WorkspaceScope::fromRequest($request), $probe);

        return response()->noContent();
    }
}
