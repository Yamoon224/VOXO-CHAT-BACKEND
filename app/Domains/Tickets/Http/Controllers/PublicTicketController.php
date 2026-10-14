<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Http\Requests\OpenPublicTicketRequest;
use App\Domains\Tickets\Http\Resources\TicketPortalResource;
use App\Domains\Tickets\Services\TicketPortalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Portail client, sans compte (section 2.7) : ouvrir une nouvelle demande. */
class PublicTicketController extends Controller
{
    public function __construct(private readonly TicketPortalService $portal) {}

    public function store(OpenPublicTicketRequest $request, string $workspace): JsonResponse
    {
        $ticket = $this->portal->openPublicTicket(
            $workspace,
            $request->string('email')->toString(),
            $request->string('name')->toString() ?: null,
            $request->string('subject')->toString(),
            $request->string('message')->toString(),
        );

        return (new TicketPortalResource($ticket))->response()->setStatusCode(201);
    }
}
