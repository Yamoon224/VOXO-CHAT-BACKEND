<?php

namespace App\Domains\Assistant\DTOs;

/** Un paramètre d'outil tel que décrit à Claude (section 2.2, P3) — jamais le détail HTTP (voir `AgentTool`). */
final readonly class ToolParameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public bool $required,
    ) {}
}
