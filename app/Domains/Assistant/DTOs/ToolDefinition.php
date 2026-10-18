<?php

namespace App\Domains\Assistant\DTOs;

/**
 * Ce que l'agent IA voit d'un outil déclaré (section 2.2, P3) : son nom, sa
 * description, ses paramètres — jamais l'URL ni les en-têtes, qui restent
 * un détail d'exécution de `AgentToolCaller`.
 */
final readonly class ToolDefinition
{
    /** @param  list<ToolParameter>  $parameters */
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
    ) {}
}
