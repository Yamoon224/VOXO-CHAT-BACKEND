<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Controllers\Concerns\VerifiesMetaWebhook;
use App\Domains\Channels\Http\Requests\InstagramInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Point d'entrée du canal Instagram (lot 5, section 2.5) : Send/Receive API de Meta. */
class InstagramInboundController extends Controller
{
    use AuthorizesInboundWebhook;
    use VerifiesMetaWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function verify(Request $request, string $workspace): Response
    {
        return $this->verifyMetaWebhook($request, $this->connections, $workspace, 'instagram');
    }

    public function store(InstagramInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'instagram',
            fn (array $credentials) => InboundSignatureVerifier::metaSignatureValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByExternalId(
            $workspace,
            'instagram',
            $request->senderIgsid(),
            null,
            $request->body(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
