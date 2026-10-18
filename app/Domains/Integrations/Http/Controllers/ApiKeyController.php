<?php

namespace App\Domains\Integrations\Http\Controllers;

use App\Domains\Integrations\Http\Requests\StoreApiKeyRequest;
use App\Domains\Integrations\Http\Resources\ApiKeyResource;
use App\Domains\Integrations\Services\ApiKeyService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeys) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ApiKeyResource::collection($this->apiKeys->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $token = $this->apiKeys->issue(
            WorkspaceScope::fromRequest($request),
            $request->user(),
            $request->string('name')->toString(),
        );

        return response()->json(['data' => ['token' => $token]], 201);
    }

    public function destroy(Request $request, string $key): Response
    {
        $this->apiKeys->revoke(WorkspaceScope::fromRequest($request), $key);

        return response()->noContent();
    }
}
