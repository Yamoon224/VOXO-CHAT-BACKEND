<?php

namespace App\Domains\Workspaces\Http\Controllers;

use App\Domains\Workspaces\Http\Requests\AcceptInvitationRequest;
use App\Domains\Workspaces\Http\Resources\InvitationPreviewResource;
use App\Domains\Workspaces\Services\InvitationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * L'invitation vue par l'invité : routes publiques, protégées par la seule
 * possession du jeton reçu par e-mail.
 */
class InvitationAcceptanceController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function show(string $token): InvitationPreviewResource
    {
        $invitation = $this->invitations->resolve($token);

        return (new InvitationPreviewResource($invitation))
            ->withAccountExists($this->invitations->inviteeHasAccount($invitation));
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $accepted = $this->invitations->accept(
            $token,
            // La route est publique : le compte connecté n'est lu que s'il y en a un.
            $request->user('sanctum'),
            $request->safe()->only(['name', 'password']),
            $request->string('device_name', 'web')->toString(),
        );

        return response()->json([
            'data' => [
                'token' => $accepted->token,
                'workspace_id' => $accepted->member->workspace_id,
            ],
        ]);
    }
}
