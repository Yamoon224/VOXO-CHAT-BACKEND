<?php

namespace App\Domains\Conversations\Http\Controllers;

use App\Domains\Conversations\Http\Requests\StoreCannedResponseRequest;
use App\Domains\Conversations\Http\Requests\UpdateCannedResponseRequest;
use App\Domains\Conversations\Http\Resources\CannedResponseResource;
use App\Domains\Conversations\Services\CannedResponseService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CannedResponseController extends Controller
{
    public function __construct(private readonly CannedResponseService $responses) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CannedResponseResource::collection($this->responses->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreCannedResponseRequest $request): JsonResponse
    {
        $response = $this->responses->create(
            WorkspaceScope::fromRequest($request),
            $request->string('title')->toString(),
            $request->string('body')->toString(),
        );

        return (new CannedResponseResource($response))->response()->setStatusCode(201);
    }

    public function update(UpdateCannedResponseRequest $request, string $cannedResponse): CannedResponseResource
    {
        return new CannedResponseResource($this->responses->update(
            WorkspaceScope::fromRequest($request),
            $cannedResponse,
            $request->string('title')->toString(),
            $request->string('body')->toString(),
        ));
    }

    public function destroy(Request $request, string $cannedResponse): Response
    {
        $this->responses->delete(WorkspaceScope::fromRequest($request), $cannedResponse);

        return response()->noContent();
    }
}
