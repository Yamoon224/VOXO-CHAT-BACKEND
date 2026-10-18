<?php

namespace App\Domains\Assistant\Contracts;

/**
 * Ce que `ClaudeAiProvider` connaît d'un outil déclaré : son nom et comment
 * l'invoquer — jamais le détail HTTP (`AgentTool`, `ToolCallerContract`),
 * pour pouvoir tester la boucle d'appel d'outils sans pilote HTTP réel.
 */
interface ToolInvokerContract
{
    /**
     * @param  array<string, mixed>  $arguments
     * @return string Le résultat, à renvoyer à Claude comme `tool_result`. Jamais une exception : un outil en échec renvoie un message d'erreur lisible.
     */
    public function invoke(string $workspaceId, string $toolName, array $arguments): string;
}
