<?php

namespace App\Domains\Assistant\Contracts;

use App\Domains\Assistant\DTOs\ToolDefinition;

/** Ce que `AssistantService` connaît des outils déclarés : lesquels proposer à Claude — jamais comment les invoquer (voir `ToolInvokerContract`). */
interface AvailableToolsContract
{
    /** @return list<ToolDefinition> */
    public function availableToolDefinitions(string $workspaceId): array;
}
