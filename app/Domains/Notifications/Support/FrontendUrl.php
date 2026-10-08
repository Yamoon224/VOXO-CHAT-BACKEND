<?php

namespace App\Domains\Notifications\Support;

/**
 * Construit les liens vers l'application web insérés dans les e-mails.
 */
final readonly class FrontendUrl
{
    public function __construct(private string $baseUrl) {}

    /** @param  array<string, string>  $query */
    public function to(string $path, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }
}
