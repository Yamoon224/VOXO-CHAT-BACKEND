<?php

namespace Tests\Support\Fakes;

use App\Domains\Automations\Contracts\AutomationResponderContract;
use App\Models\Conversation;

/** Doublure de test : ne prend jamais la main, comme une workspace sans aucun scénario publié. */
final class NullAutomationResponder implements AutomationResponderContract
{
    public function applyTo(Conversation $conversation, string $messageBody, bool $isNewConversation): bool
    {
        return false;
    }
}
