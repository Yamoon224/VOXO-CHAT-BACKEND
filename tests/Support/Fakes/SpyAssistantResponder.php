<?php

namespace Tests\Support\Fakes;

use App\Domains\Assistant\Contracts\AssistantResponderContract;

/** Doublure de test : enregistre les conversations pour lesquelles une réponse a été déclenchée. */
final class SpyAssistantResponder implements AssistantResponderContract
{
    /** @var list<string> */
    private array $calls = [];

    public function respondToConversation(string $conversationId): void
    {
        $this->calls[] = $conversationId;
    }

    /** @return list<string> */
    public function calls(): array
    {
        return $this->calls;
    }
}
