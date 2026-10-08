<?php

namespace App\Domains\Knowledge\Contracts;

use App\Domains\Knowledge\DTOs\CrawledPage;

/**
 * Explore un site web à partir d'une URL et, si fournie, d'un plan de site.
 */
interface WebCrawlerContract
{
    /** @return list<CrawledPage> */
    public function crawl(string $url, ?string $sitemapUrl): array;
}
