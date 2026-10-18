<?php

namespace Tests\Support\Fakes;

use App\Domains\Assistant\Contracts\ToolInvokerContract;

/** Doublure de test : enregistre chaque invocation, résultat configurable par nom d'outil. */
final class SpyToolInvoker implements ToolInvokerContract
{
    /** @var array<string, string> */
    private array $results = [];

    /** @var list<array{workspaceId: string, toolName: string, arguments: array<string, mixed>}> */
    private array $calls = [];

    public function willReturn(string $toolName, string $result): void
    {
        $this->results[$toolName] = $result;
    }

    public function invoke(string $workspaceId, string $toolName, array $arguments): string
    {
        $this->calls[] = ['workspaceId' => $workspaceId, 'toolName' => $toolName, 'arguments' => $arguments];

        return $this->results[$toolName] ?? 'Résultat simulé.';
    }

    /** @return list<array{workspaceId: string, toolName: string, arguments: array<string, mixed>}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
