<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Tickets\Http\Requests\OpenTicketFromConversationRequest;
use App\Domains\Tickets\Http\Resources\TicketResource;
use App\Domains\Tickets\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/** Action unique : un agent transforme une conversation existante en ticket — section 2.7 du cahier des charges. */
class OpenTicketFromConversationController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function store(OpenTicketFromConversationRequest $request, string $conversation): JsonResponse
    {
        $dueAt = $request->string('due_at')->toString();

        $ticket = $this->tickets->openFromConversation(
            WorkspaceScope::fromRequest($request),
            $conversation,
            $request->priority(),
            $dueAt === '' ? null : Carbon::parse($dueAt),
        );

        return (new TicketResource($ticket))->response()->setStatusCode(201);
    }
}
