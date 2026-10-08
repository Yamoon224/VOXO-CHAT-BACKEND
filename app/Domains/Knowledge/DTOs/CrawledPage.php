<?php

namespace App\Domains\Knowledge\DTOs;

/** Une page récupérée par le robot d'exploration, avant tout découpage. */
final readonly class CrawledPage
{
    public function __construct(
        public string $url,
        public string $title,
        public string $text,
    ) {}
}
