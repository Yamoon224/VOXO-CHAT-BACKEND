<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\TelegramChannelProviderContract;
use Illuminate\Support\Facades\Http;

/** Pilote par défaut : Bot API de Telegram. */
final class TelegramBotApiProvider implements TelegramChannelProviderContract
{
    public function sendReply(string $toChatId, string $body, array $credentials): void
    {
        $botToken = (string) ($credentials['bot_token'] ?? '');

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $toChatId,
            'text' => $body,
        ])->throw();
    }
}
