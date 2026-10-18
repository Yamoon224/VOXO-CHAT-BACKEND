<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Requests\TelephonyInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Point d'entrée du canal téléphonie (lot 7, section 2.5, P3) : callback de
 * statut d'appel vocal (forme Twilio-like) — journal d'appel uniquement, pas
 * de téléphonie temps réel (voix/IVR).
 */
class TelephonyInboundController extends Controller
{
    use AuthorizesInboundWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function store(TelephonyInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'telephony',
            fn (array $credentials) => InboundSignatureVerifier::twilioSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByPhone(
            $workspace,
            'telephony',
            $request->string('From')->toString(),
            null,
            $request->summary(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
