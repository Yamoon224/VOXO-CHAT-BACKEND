<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Controllers\Concerns\VerifiesMetaWebhook;
use App\Domains\Channels\Http\Requests\WhatsAppInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Point d'entrée du canal WhatsApp (lot 5, section 2.5) : WhatsApp Cloud API. */
class WhatsAppInboundController extends Controller
{
    use AuthorizesInboundWebhook;
    use VerifiesMetaWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function verify(Request $request, string $workspace): Response
    {
        return $this->verifyMetaWebhook($request, $this->connections, $workspace, 'whatsapp');
    }

    public function store(WhatsAppInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'whatsapp',
            fn (array $credentials) => InboundSignatureVerifier::metaSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByPhone(
            $workspace,
            'whatsapp',
            $request->fromPhone(),
            $request->senderName(),
            $request->body(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
