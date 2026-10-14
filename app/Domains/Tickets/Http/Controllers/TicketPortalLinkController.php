<?php

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Http\Requests\RequestTicketPortalLinkRequest;
use App\Domains\Tickets\Services\TicketPortalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/** Réémet le lien de portail d'une adresse connue — répond 204 qu'elle le soit ou non, même garde que le mot de passe oublié. */
class TicketPortalLinkController extends Controller
{
    public function __construct(private readonly TicketPortalService $portal) {}

    public function store(RequestTicketPortalLinkRequest $request, string $workspace): Response
    {
        $this->portal->resendLink($workspace, $request->string('email')->toString());

        return response()->noContent();
    }
}
