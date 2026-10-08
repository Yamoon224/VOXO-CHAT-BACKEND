<?php

namespace App\Domains\Knowledge\Crawling;

use App\Domains\Knowledge\Contracts\WebCrawlerContract;
use App\Domains\Knowledge\DTOs\CrawledPage;

/** Doublure de test : renvoie des pages préparées à l'avance, sans requête réseau. */
final class ArrayWebCrawler implements WebCrawlerContract
{
    /** @var list<CrawledPage> */
    private array $pages = [];

    /** @param  list<CrawledPage>  $pages */
    public function queue(array $pages): void
    {
        $this->pages = $pages;
    }

    public function crawl(string $url, ?string $sitemapUrl): array
    {
        return $this->pages;
    }
}
