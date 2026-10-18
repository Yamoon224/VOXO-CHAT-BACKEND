<?php

namespace App\Domains\StatusPage\DTOs;

/** Résultat d'une vérification de sonde (section 2.11), indépendant du pilote HTTP. */
final readonly class ProbeCheckResult
{
    public function __construct(
        public bool $isUp,
        public ?int $responseTimeMs,
    ) {}
}
