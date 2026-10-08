<?php

namespace App\Domains\Knowledge\Crawling;

use App\Domains\Knowledge\Contracts\WebCrawlerContract;
use App\Domains\Knowledge\DTOs\CrawledPage;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Throwable;

/**
 * Exploration simple : suit un plan de site s'il est fourni (liste des
 * `<loc>`), sinon ne récupère que l'URL de départ. Pas de suivi récursif des
 * liens internes — hors scope pour cette passe, à faire évoluer derrière ce
 * même contrat si le besoin se confirme.
 */
final class SimpleWebCrawler implements WebCrawlerContract
{
    public function __construct(private readonly int $maxPages) {}

    public function crawl(string $url, ?string $sitemapUrl): array
    {
        $urls = $sitemapUrl !== null ? $this->urlsFromSitemap($sitemapUrl) : [$url];
        $urls = array_slice(array_values(array_unique($urls)), 0, $this->maxPages);

        $pages = [];

        foreach ($urls as $pageUrl) {
            $page = $this->fetchPage($pageUrl);

            if ($page !== null) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /** @return list<string> */
    private function urlsFromSitemap(string $sitemapUrl): array
    {
        try {
            $body = Http::timeout(30)->get($sitemapUrl)->throw()->body();
            $xml = simplexml_load_string($body);

            if ($xml === false) {
                return [$sitemapUrl];
            }

            $locations = [];

            foreach ($xml->url ?? [] as $entry) {
                $locations[] = (string) $entry->loc;
            }

            return $locations !== [] ? $locations : [$sitemapUrl];
        } catch (Throwable) {
            return [$sitemapUrl];
        }
    }

    private function fetchPage(string $url): ?CrawledPage
    {
        try {
            $response = Http::timeout(30)->get($url)->throw();
            $crawler = new DomCrawler($response->body());

            $crawler->filter('script, style')->each(fn (DomCrawler $node) => $node->getNode(0)?->parentNode?->removeChild($node->getNode(0)));

            $title = $crawler->filter('title')->count() > 0 ? trim($crawler->filter('title')->text('')) : $url;
            $text = trim(preg_replace('/\s+/u', ' ', $crawler->filter('body')->count() > 0 ? $crawler->filter('body')->text('') : $crawler->text('')) ?? '');

            return new CrawledPage($url, $title, $text);
        } catch (Throwable) {
            return null;
        }
    }
}
