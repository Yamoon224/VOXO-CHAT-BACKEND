<?php

namespace App\Domains\Channels\Services;

use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Models\Conversation;

/** Logique commune aux connecteurs entrants (`email`, `contact_form`) : rattacher le contact, puis la conversation. */
final class InboundChannelService
{
    public function __construct(
        private readonly ContactLinkContract $contacts,
        private readonly VisitorConversationContract $conversations,
    ) {}

    public function receive(string $workspaceId, string $channel, string $email, ?string $name, string $body): Conversation
    {
        $contactId = $this->contacts->findOrCreateIdByEmail($workspaceId, $email, $name);

        return $this->conversations->receiveChannelMessage($workspaceId, $channel, $email, $body, $name, $email, $contactId);
    }

    /** Même mécanique, pour un canal identifié par numéro de téléphone (lot 5 : WhatsApp, SMS). */
    public function receiveByPhone(string $workspaceId, string $channel, string $phone, ?string $name, string $body): Conversation
    {
        $contactId = $this->contacts->findOrCreateIdByPhone($workspaceId, $phone, $name);

        return $this->conversations->receiveChannelMessage($workspaceId, $channel, $phone, $body, $name, null, $contactId);
    }

    /**
     * Même mécanique, pour un canal identifié par un identifiant propre au
     * prestataire (lot 5 : PSID Messenger, IGSID Instagram, chat id Telegram)
     * — ni e-mail ni téléphone connus côté visiteur.
     */
    public function receiveByExternalId(string $workspaceId, string $channel, string $externalId, ?string $name, string $body): Conversation
    {
        $contactId = $this->contacts->findOrCreateIdByExternalId($workspaceId, $channel, $externalId, $name);

        return $this->conversations->receiveChannelMessage($workspaceId, $channel, $externalId, $body, $name, null, $contactId);
    }
}
