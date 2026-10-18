<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Http\Requests\StoreStatusMaintenanceRequest;
use App\Domains\StatusPage\Http\Requests\UpdateStatusMaintenanceRequest;
use App\Domains\StatusPage\Http\Resources\StatusMaintenanceResource;
use App\Domains\StatusPage\Services\StatusMaintenanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StatusMaintenanceController extends Controller
{
    public function __construct(private readonly StatusMaintenanceService $maintenances) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return StatusMaintenanceResource::collection($this->maintenances->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreStatusMaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->maintenances->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new StatusMaintenanceResource($maintenance))->response()->setStatusCode(201);
    }

    public function update(UpdateStatusMaintenanceRequest $request, string $maintenance): StatusMaintenanceResource
    {
        return new StatusMaintenanceResource(
            $this->maintenances->update(WorkspaceScope::fromRequest($request), $maintenance, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $maintenance): Response
    {
        $this->maintenances->delete(WorkspaceScope::fromRequest($request), $maintenance);

        return response()->noContent();
    }
}
