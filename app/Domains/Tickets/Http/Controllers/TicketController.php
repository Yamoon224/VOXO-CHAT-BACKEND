<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Tickets\Http\Requests\ListTicketsRequest;
use App\Domains\Tickets\Http\Requests\UpdateTicketRequest;
use App\Domains\Tickets\Http\Resources\TicketResource;
use App\Domains\Tickets\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(ListTicketsRequest $request): AnonymousResourceCollection
    {
        return TicketResource::collection($this->tickets->list(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function show(Request $request, string $ticket): TicketResource
    {
        return new TicketResource($this->tickets->get(WorkspaceScope::fromRequest($request), $ticket));
    }

    public function update(UpdateTicketRequest $request, string $ticket): TicketResource
    {
        $attributes = $request->validated();

        if (array_key_exists('due_at', $attributes) && $attributes['due_at'] !== null) {
            $attributes['due_at'] = Carbon::parse($attributes['due_at']);
        }

        return new TicketResource(
            $this->tickets->update(WorkspaceScope::fromRequest($request), $ticket, $attributes),
        );
    }
}
