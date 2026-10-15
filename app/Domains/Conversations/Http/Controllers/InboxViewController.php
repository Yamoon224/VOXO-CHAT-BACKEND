<?php

namespace App\Domains\Conversations\Http\Controllers;

use App\Domains\Conversations\Http\Requests\StoreInboxViewRequest;
use App\Domains\Conversations\Http\Requests\UpdateInboxViewRequest;
use App\Domains\Conversations\Http\Resources\InboxViewResource;
use App\Domains\Conversations\Services\InboxViewService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InboxViewController extends Controller
{
    public function __construct(private readonly InboxViewService $views) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return InboxViewResource::collection($this->views->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreInboxViewRequest $request): JsonResponse
    {
        $view = $this->views->create(
            WorkspaceScope::fromRequest($request),
            $request->string('name')->toString(),
            $request->filters(),
            $request->position(),
        );

        return (new InboxViewResource($view))->response()->setStatusCode(201);
    }

    public function update(UpdateInboxViewRequest $request, string $view): InboxViewResource
    {
        return new InboxViewResource($this->views->update(
            WorkspaceScope::fromRequest($request),
            $view,
            $request->string('name')->toString(),
            $request->filters(),
            $request->position(),
        ));
    }

    public function destroy(Request $request, string $view): Response
    {
        $this->views->delete(WorkspaceScope::fromRequest($request), $view);

        return response()->noContent();
    }
}
