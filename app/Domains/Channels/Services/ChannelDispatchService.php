<?php

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Domains\Channels\Contracts\EmailChannelProviderContract;
use App\Domains\Channels\Contracts\InstagramChannelProviderContract;
use App\Domains\Channels\Contracts\LineChannelProviderContract;
use App\Domains\Channels\Contracts\MessengerChannelProviderContract;
use App\Domains\Channels\Contracts\OutboundChannelDispatcherContract;
use App\Domains\Channels\Contracts\SmsChannelProviderContract;
use App\Domains\Channels\Contracts\TelegramChannelProviderContract;
use App\Domains\Channels\Contracts\TelephonyChannelProviderContract;
use App\Domains\Channels\Contracts\ViberChannelProviderContract;
use App\Domains\Channels\Contracts\WhatsAppChannelProviderContract;
use App\Domains\Channels\Contracts\XChannelProviderContract;

/** Implémente `OutboundChannelDispatcherContract` : relaie une réponse publique vers son canal d'origine. */
final class ChannelDispatchService implements OutboundChannelDispatcherContract
{
    public function __construct(
        private readonly ChannelEmailSettingsRepositoryContract $emailSettings,
        private readonly EmailChannelProviderContract $emailProvider,
        private readonly ChannelConnectionRepositoryContract $connections,
        private readonly WhatsAppChannelProviderContract $whatsApp,
        private readonly MessengerChannelProviderContract $messenger,
        private readonly InstagramChannelProviderContract $instagram,
        private readonly TelegramChannelProviderContract $telegram,
        private readonly SmsChannelProviderContract $sms,
        private readonly XChannelProviderContract $x,
        private readonly ViberChannelProviderContract $viber,
        private readonly LineChannelProviderContract $line,
        private readonly TelephonyChannelProviderContract $telephony,
    ) {}

    public function dispatchReply(string $workspaceId, string $channel, ?string $recipientAddress, string $body): void
    {
        if ($recipientAddress === null) {
            return;
        }

        if ($channel === 'email') {
            $this->dispatchEmail($workspaceId, $recipientAddress, $body);

            return;
        }

        $connection = $this->connections->findForWorkspace($workspaceId, $channel);

        if ($connection === null || ! $connection->enabled) {
            return;
        }

        match ($channel) {
            'whatsapp' => $this->whatsApp->sendReply($recipientAddress, $body, $connection->credentials),
            'messenger' => $this->messenger->sendReply($recipientAddress, $body, $connection->credentials),
            'instagram' => $this->instagram->sendReply($recipientAddress, $body, $connection->credentials),
            'telegram' => $this->telegram->sendReply($recipientAddress, $body, $connection->credentials),
            'sms' => $this->sms->sendReply($recipientAddress, $body, $connection->credentials),
            'x' => $this->x->sendReply($recipientAddress, $body, $connection->credentials),
            'viber' => $this->viber->sendReply($recipientAddress, $body, $connection->credentials),
            'line' => $this->line->sendReply($recipientAddress, $body, $connection->credentials),
            'telephony' => $this->telephony->sendReply($recipientAddress, $body, $connection->credentials),
            default => null,
        };
    }

    private function dispatchEmail(string $workspaceId, string $recipientEmail, string $body): void
    {
        $settings = $this->emailSettings->findOrCreateForWorkspace($workspaceId);

        if (! $settings->enabled || $settings->reply_from_email === null) {
            return;
        }

        $this->emailProvider->sendReply(
            $recipientEmail,
            $settings->reply_from_email,
            $settings->reply_from_name ?? $settings->reply_from_email,
            'Re: Votre conversation',
            $body,
        );
    }
}
