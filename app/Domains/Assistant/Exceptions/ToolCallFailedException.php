<?php

namespace App\Domains\Assistant\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** L'appel HTTP d'un outil déclaré a échoué (section 2.2, P3) — jamais exposée au visiteur, convertie en message pour Claude. */
final class ToolCallFailedException extends DomainException
{
    public static function make(string $toolName): self
    {
        return new self("L'appel de l'outil « {$toolName} » a échoué.", 'tool_call_failed', 502);
    }
}
