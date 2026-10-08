<?php

namespace App\Domains\Widget\Http\Controllers;

use App\Domains\Widget\Http\Requests\StartVisitorSessionRequest;
use App\Domains\Widget\Services\VisitorSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VisitorSessionController extends Controller
{
    public function __construct(private readonly VisitorSessionService $sessions) {}

    public function store(StartVisitorSessionRequest $request, string $workspace): JsonResponse
    {
        return response()->json(['data' => $this->sessions->start($workspace)], 201);
    }
}
