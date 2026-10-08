<?php

namespace App\Domains\Workspaces\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Http\Requests\StoreInvitationRequest;
use App\Domains\Workspaces\Http\Resources\InvitationResource;
use App\Domains\Workspaces\Services\InvitationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Invitations de l'espace de travail courant, vues par ceux qui gèrent
 * l'équipe.
 */
class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return InvitationResource::collection($this->invitations->pending(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $invitation = $this->invitations->invite(
            WorkspaceScope::fromRequest($request),
            $request->user(),
            $request->string('email')->toString(),
            $request->role(),
        );

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $invitation): Response
    {
        $this->invitations->revoke(WorkspaceScope::fromRequest($request), $invitation);

        return response()->noContent();
    }
}
