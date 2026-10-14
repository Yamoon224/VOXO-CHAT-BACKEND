<?php

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Domains\Channels\Contracts\EmailChannelProviderContract;
use App\Domains\Channels\Contracts\OutboundChannelDispatcherContract;

/** Implémente `OutboundChannelDispatcherContract` : relaie une réponse publique vers son canal d'origine. */
final class ChannelDispatchService implements OutboundChannelDispatcherContract
{
    public function __construct(
        private readonly ChannelEmailSettingsRepositoryContract $settings,
        private readonly EmailChannelProviderContract $emailProvider,
    ) {}

    public function dispatchReply(string $workspaceId, string $channel, ?string $recipientEmail, string $body): void
    {
        if ($channel !== 'email' || $recipientEmail === null) {
            return;
        }

        $settings = $this->settings->findOrCreateForWorkspace($workspaceId);

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
