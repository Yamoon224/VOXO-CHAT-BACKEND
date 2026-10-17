<?php

namespace App\Domains\Auth\DTOs;

use Illuminate\Support\Carbon;

/** Lecture d'une clé d'API (lot 6, section 2.14) — jamais le jeton en clair, déjà remis une seule fois à l'émission. */
final readonly class ApiKeyInfo
{
    public function __construct(
        public string $id,
        public string $name,
        public string $createdByName,
        public ?Carbon $lastUsedAt,
        public Carbon $createdAt,
    ) {}
}
