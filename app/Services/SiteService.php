<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

class SiteService
{
    protected string $myBaseUrl;

    public function __construct(?string $myBaseUrl = null)
    {
        // set 1 lần, dùng toàn bộ service
        $this->myBaseUrl = rtrim($myBaseUrl ?: config('app.url'), '/');
    }

    public function extractBoxCurrentWeather(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        $node = $crawler->filter('section.section-current.home-weather-current');
        if ($node->count() === 0) return '';

        $boxHtml = $this->outerHTML($node->getNode(0));
        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }

    public function extractBoxFeaturedWeather(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        $node = $crawler->filterXPath(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' pd-h ') 
              and contains(concat(' ', normalize-space(@class), ' '), ' bs-h ')]
              [.//h2[contains(normalize-space(.), 'Thời tiết nổi bật')]]"
        );

        if ($node->count() === 0) return '';

        $boxHtml = $this->outerHTML($node->getNode(0));
        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }

    private function outerHTML(\DOMNode $node): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->appendChild($doc->importNode($node, true));
        return $doc->saveHTML();
    }

    private function rewriteFragmentUrlsKeepPath(string $fragmentHtml, string $upstreamBase): string
    {
        $upstreamBase = rtrim($upstreamBase, '/'); // https://thoitiet.tv
        $myBaseUrl    = $this->myBaseUrl;          // https://domain.com

        // 1) //cdn.weatherapi.com/weather/... => {my}/weather/...
        $fragmentHtml = preg_replace(
            '#//cdn\.weatherapi\.com/weather/#i',
            $myBaseUrl . '/weather/',
            $fragmentHtml
        );

        // 2) https://cdn.weatherapi.com/weather/... => {my}/weather/...
        $fragmentHtml = preg_replace(
            '#https?://cdn\.weatherapi\.com/weather/#i',
            $myBaseUrl . '/weather',
            $fragmentHtml
        );

        // 3) https://thoitiet.tv/... => {my}/...
        $fragmentHtml = str_replace($upstreamBase, $myBaseUrl, $fragmentHtml);

        // 4) href="/..." => href="{my}/..."
        $fragmentHtml = preg_replace_callback(
            '#\shref=(["\'])(/[^"\']*)\1#i',
            function ($m) use ($myBaseUrl) {
                return ' href=' . $m[1] . $myBaseUrl . $m[2] . $m[1];
            },
            $fragmentHtml
        );

        // 5) style url('/...') => url('{my}/...')
        $fragmentHtml = preg_replace_callback(
            '#url\((["\']?)(/[^)\'"]+)\1\)#i',
            function ($m) use ($myBaseUrl) {
                return 'url(' . $m[1] . $myBaseUrl . $m[2] . $m[1] . ')';
            },
            $fragmentHtml
        );

        return $fragmentHtml;
    }

    public function extractBoxCategorySidebarWeather(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        // box sidebar ở trang category/genre
        $node = $crawler->filter('section.section-current.category-weather-current');

        if ($node->count() === 0) {
            return '';
        }

        $boxHtml = $this->outerHTML($node->getNode(0));

        // rewrite link/assets để chạy trên domain mình
        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }
}
