<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Controllers\Concerns\VerifiesMetaWebhook;
use App\Domains\Channels\Http\Requests\MessengerInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Point d'entrée du canal Messenger (lot 5, section 2.5) : Send/Receive API
 * de Meta. Le PSID n'expose ni nom ni e-mail directement dans le webhook —
 * un appel séparé au Graph API donnerait le profil, hors périmètre ici
 * (même posture que Messenger/Instagram pour le nom du contact).
 */
class MessengerInboundController extends Controller
{
    use AuthorizesInboundWebhook;
    use VerifiesMetaWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function verify(Request $request, string $workspace): Response
    {
        return $this->verifyMetaWebhook($request, $this->connections, $workspace, 'messenger');
    }

    public function store(MessengerInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'messenger',
            fn (array $credentials) => InboundSignatureVerifier::metaSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByExternalId(
            $workspace,
            'messenger',
            $request->senderPsid(),
            null,
            $request->body(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
