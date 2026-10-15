<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Requests\SmsInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Point d'entrée du canal SMS (lot 5, section 2.5) : forme Twilio-like. */
class SmsInboundController extends Controller
{
    use AuthorizesInboundWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function store(SmsInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'sms',
            fn (array $credentials) => InboundSignatureVerifier::twilioSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByPhone(
            $workspace,
            'sms',
            $request->string('From')->toString(),
            null,
            $request->string('Body')->toString(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
