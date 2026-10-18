<?php

namespace Tests\Support\Fakes;

use App\Domains\Assistant\Contracts\AvailableToolsContract;

/** Doublure de test : aucun outil déclaré, comme un espace qui n'en a configuré aucun. */
final class NullAvailableTools implements AvailableToolsContract
{
    public function availableToolDefinitions(string $workspaceId): array
    {
        return [];
    }
}
