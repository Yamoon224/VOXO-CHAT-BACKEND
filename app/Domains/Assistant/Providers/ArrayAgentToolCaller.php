<?php

namespace App\Domains\Assistant\Providers;

use App\Domains\Assistant\Contracts\ToolCallerContract;
use App\Models\AgentTool;

/** Doublure de test : pas d'appel réseau, résultat configurable par nom d'outil. */
final class ArrayAgentToolCaller implements ToolCallerContract
{
    /** @var array<string, string> */
    private array $results = [];

    /** @var list<array{tool: string, arguments: array<string, mixed>}> */
    private array $calls = [];

    public function willReturn(string $toolName, string $result): void
    {
        $this->results[$toolName] = $result;
    }

    public function call(AgentTool $tool, array $arguments): string
    {
        $this->calls[] = ['tool' => $tool->name, 'arguments' => $arguments];

        return $this->results[$tool->name] ?? 'Résultat simulé.';
    }

    /** @return list<array{tool: string, arguments: array<string, mixed>}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
