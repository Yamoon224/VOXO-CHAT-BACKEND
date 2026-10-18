<?php

namespace App\Domains\Assistant\Contracts;

use App\Domains\Assistant\Exceptions\ToolCallFailedException;
use App\Models\AgentTool;

/** Pilote d'appel HTTP d'un outil déclaré, choisi par configuration (section 2.2, P3) — même forme que `PaymentGatewayContract`. */
interface ToolCallerContract
{
    /**
     * @param  array<string, mixed>  $arguments
     *
     * @throws ToolCallFailedException
     */
    public function call(AgentTool $tool, array $arguments): string;
}
