<?php

namespace App\Domains\Billing\Http\Controllers;

use App\Domains\Billing\Http\Resources\PlanResource;
use App\Domains\Billing\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $plans) {}

    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->plans->listActive());
    }
}
