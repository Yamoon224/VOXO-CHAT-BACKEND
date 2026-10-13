<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Http\Requests\StoreContactSegmentRequest;
use App\Domains\Contacts\Http\Requests\UpdateContactSegmentRequest;
use App\Domains\Contacts\Http\Resources\ContactResource;
use App\Domains\Contacts\Http\Resources\ContactSegmentResource;
use App\Domains\Contacts\Services\ContactSegmentService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactSegmentController extends Controller
{
    public function __construct(private readonly ContactSegmentService $segments) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ContactSegmentResource::collection(
            $this->segments->list(WorkspaceScope::fromRequest($request)->workspaceId),
        );
    }

    public function store(StoreContactSegmentRequest $request): JsonResponse
    {
        $segment = $this->segments->create(WorkspaceScope::fromRequest($request)->workspaceId, $request->validated());

        return (new ContactSegmentResource($segment))->response()->setStatusCode(201);
    }

    public function update(UpdateContactSegmentRequest $request, string $segment): ContactSegmentResource
    {
        return new ContactSegmentResource(
            $this->segments->update(WorkspaceScope::fromRequest($request)->workspaceId, $segment, $request->validated()),
        );
    }

    public function destroy(Request $request, string $segment): Response
    {
        $this->segments->delete(WorkspaceScope::fromRequest($request)->workspaceId, $segment);

        return response()->noContent();
    }

    public function contacts(Request $request, string $segment): AnonymousResourceCollection
    {
        return ContactResource::collection(
            $this->segments->matching(WorkspaceScope::fromRequest($request)->workspaceId, $segment),
        );
    }
}
