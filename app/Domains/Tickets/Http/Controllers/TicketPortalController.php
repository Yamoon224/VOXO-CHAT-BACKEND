<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Http\Requests\ShowTicketPortalRequest;
use App\Domains\Tickets\Http\Resources\TicketPortalMessageResource;
use App\Domains\Tickets\Http\Resources\TicketPortalResource;
use App\Domains\Tickets\Services\TicketPortalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Portail client, sans compte (section 2.7) : retrouver ses tickets et leur fil, par lien signé. */
class TicketPortalController extends Controller
{
    public function __construct(private readonly TicketPortalService $portal) {}

    public function index(ShowTicketPortalRequest $request): AnonymousResourceCollection
    {
        return TicketPortalResource::collection($this->portal->listForContact($request->string('token')->toString()));
    }

    public function show(ShowTicketPortalRequest $request, string $ticket): JsonResponse
    {
        $found = $this->portal->getForContact($request->string('token')->toString(), $ticket);
        $messages = $this->portal->messagesFor($found);

        return response()->json([
            'data' => [
                'ticket' => (new TicketPortalResource($found))->toArray($request),
                'messages' => TicketPortalMessageResource::collection($messages),
            ],
        ]);
    }
}
