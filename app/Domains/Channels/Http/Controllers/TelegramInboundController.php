<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Http\Controllers\Concerns\AuthorizesInboundWebhook;
use App\Domains\Channels\Http\Requests\TelegramInboundRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Domains\Channels\Support\InboundSignatureVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Point d'entrée du canal Telegram (lot 5, section 2.5) : Bot API, pas de
 * poignée de main GET contrairement aux produits Meta — Telegram appelle
 * directement l'URL de webhook qu'on lui donne en configurant le bot.
 */
class TelegramInboundController extends Controller
{
    use AuthorizesInboundWebhook;

    public function __construct(
        private readonly InboundChannelService $inbound,
        private readonly ChannelConnectionRepositoryContract $connections,
    ) {}

    public function store(TelegramInboundRequest $request, string $workspace): JsonResponse
    {
        $authorized = $this->authorizeInboundWebhook(
            $this->connections,
            $workspace,
            'telegram',
            fn (array $credentials) => InboundSignatureVerifier::telegramSecretValid($request, $credentials),
        );

        if ($authorized instanceof JsonResponse) {
            return $authorized;
        }

        $conversation = $this->inbound->receiveByExternalId(
            $workspace,
            'telegram',
            $request->chatId(),
            $request->senderName(),
            $request->body(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
