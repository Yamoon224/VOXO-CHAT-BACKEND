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
}
