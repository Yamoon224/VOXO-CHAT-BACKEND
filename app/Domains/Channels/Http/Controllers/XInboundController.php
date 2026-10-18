<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Requests\XInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Point d'entrée du canal X (Twitter) (lot 7, section 2.5, P3) : Account Activity API, pas de poignée de main GET. */
class XInboundController extends Controller
{
    use AuthorizesInboundWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function store(XInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'x',
            fn (array $credentials) => InboundSignatureVerifier::xSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByExternalId(
            $workspace,
            'x',
            $request->senderId(),
            $request->senderName(),
            $request->body(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
