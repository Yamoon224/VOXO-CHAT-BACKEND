<?php

namespace App\Domains\Knowledge\Contracts;

/**
 * Explore un site web à partir d'une URL et, si fournie, d'un plan de site.
 */
interface WebCrawlerContract
{
    /** @return list<\App\Domains\Knowledge\DTOs\CrawledPage> */
    public function crawl(string $url, ?string $sitemapUrl): array;
}
