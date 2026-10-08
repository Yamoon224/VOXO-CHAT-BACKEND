<?php

namespace App\Domains\Workspaces\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Http\Requests\StoreWorkspaceRequest;
use App\Domains\Workspaces\Http\Requests\UpdateWorkspaceRequest;
use App\Domains\Workspaces\Http\Resources\MembershipResource;
use App\Domains\Workspaces\Http\Resources\WorkspaceResource;
use App\Domains\Workspaces\Services\WorkspaceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkspaceController extends Controller
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /** Les espaces de travail de l'appelant, avec le rôle qu'il y tient. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return MembershipResource::collection($this->workspaces->membershipsOf($request->user()));
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $membership = $this->workspaces->provision(
            $request->user()->id,
            $request->string('name')->toString(),
        );

        return (new MembershipResource($membership))->response()->setStatusCode(201);
    }

    /** L'espace de travail porté par le jeton. */
    public function show(Request $request): WorkspaceResource
    {
        return new WorkspaceResource($this->workspaces->current(WorkspaceScope::fromRequest($request)));
    }

    public function update(UpdateWorkspaceRequest $request): WorkspaceResource
    {
        return new WorkspaceResource(
            $this->workspaces->update(WorkspaceScope::fromRequest($request), $request->validated()),
        );
    }

    public function switch(Request $request, string $workspace): MembershipResource
    {
        return new MembershipResource($this->workspaces->switchTo($request->user(), $workspace));
    }
}
