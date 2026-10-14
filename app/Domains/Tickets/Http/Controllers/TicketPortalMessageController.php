<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Http\Requests\PostTicketPortalMessageRequest;
use App\Domains\Tickets\Http\Resources\TicketPortalResource;
use App\Domains\Tickets\Services\TicketPortalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Le client répond sur son ticket, par lien signé — même action que l'agent, vue du portail. */
class TicketPortalMessageController extends Controller
{
    public function __construct(private readonly TicketPortalService $portal) {}

    public function store(PostTicketPortalMessageRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->portal->postMessage(
            $request->string('token')->toString(),
            $ticket,
            $request->string('body')->toString(),
        );

        return (new TicketPortalResource($updated))->response()->setStatusCode(201);
    }
}
