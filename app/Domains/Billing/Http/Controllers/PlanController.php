<?php

namespace App\Domains\Billing\Http\Controllers;

use App\Domains\Billing\Http\Requests\StorePlanRequest;
use App\Domains\Billing\Http\Requests\UpdatePlanRequest;
use App\Domains\Billing\Http\Resources\PlanResource;
use App\Domains\Billing\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $plans) {}

    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->plans->listActive());
    }

    /** Vue console plateforme : tous les paliers, actifs ou non. */
    public function adminIndex(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->plans->listAll());
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->plans->create($request->validated());

        return (new PlanResource($plan))->response()->setStatusCode(201);
    }

    public function update(UpdatePlanRequest $request, string $plan): PlanResource
    {
        return new PlanResource($this->plans->update($plan, $request->validated()));
    }

    public function destroy(string $plan): Response
    {
        $this->plans->delete($plan);

        return response()->noContent();
    }
}
