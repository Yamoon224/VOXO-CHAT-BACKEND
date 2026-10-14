<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Http\Requests\InboundEmailRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Point d'entrée du canal e-mail (section 2.5) : un prestataire entrant (non encore choisi) y relaiera chaque e-mail reçu. */
class InboundEmailController extends Controller
{
    public function __construct(private readonly InboundChannelService $inbound) {}

    public function store(InboundEmailRequest $request, string $workspace): JsonResponse
    {
        $conversation = $this->inbound->receive(
            $workspace,
            'email',
            $request->string('from_email')->toString(),
            $request->string('from_name')->toString() ?: null,
            $request->string('body')->toString(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
