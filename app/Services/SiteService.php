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
        $upstreamBase = rtrim($upstreamBase, '/');
        $myBaseUrl    = $this->myBaseUrl;

        // 1) Force riêng weather/64x64/* => full CDN path
        $fragmentHtml = preg_replace_callback(
            '#((?:https?:)?//[^"\')\s]+)?(/?weather/64x64/[^"\')\s]+)#i',
            function ($m) {
                $path = ltrim($m[2], '/');
                return 'https://cdn.weatherapi.com/' . $path;
            },
            $fragmentHtml
        );

        // 2) Rewrite CDN weather về local, nhưng bỏ qua weather/64x64/*
        $fragmentHtml = preg_replace(
            '#//cdn\.weatherapi\.com/weather/(?!64x64/)#i',
            $myBaseUrl . '/weather/',
            $fragmentHtml
        );

        $fragmentHtml = preg_replace(
            '#https?://cdn\.weatherapi\.com/weather/(?!64x64/)#i',
            $myBaseUrl . '/weather/',
            $fragmentHtml
        );

        $fragmentHtml = str_replace($upstreamBase, $myBaseUrl, $fragmentHtml);

        $fragmentHtml = preg_replace_callback(
            '#\shref=(["\'])(/[^"\']*)\1#i',
            function ($m) use ($myBaseUrl) {
                return ' href=' . $m[1] . $myBaseUrl . $m[2] . $m[1];
            },
            $fragmentHtml
        );

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

    public function extractHeadMeta(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        $head = $crawler->filter('head');
        if ($head->count() === 0) return '';

        // lấy nguyên nội dung trong <head> (không lấy cả <head> tag)
        $node = $head->getNode(0);
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $node->ownerDocument->saveHTML($child);
        }

        // rewrite tất cả link/assets trong head về domain mình
        return $this->rewriteFragmentUrlsKeepPath($inner, $upstreamBase);
    }

    public function extractCityBreadcrumb(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);
        $node = $crawler->filter('div.no-border.mb-0.mt-3.rounded-0.rounded-top');


        if ($node->count() === 0) {
            return '';
        }

        $boxHtml = $this->outerHTML($node->getNode(0));

        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }

    public function extractCityTabsNav(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        // target: nav.navbar-dark.bg-weather-primary.menu-location
        $node = $crawler->filter('nav.navbar-dark.bg-weather-primary.menu-location');

        if ($node->count() === 0) {
            return '';
        }

        $boxHtml = $this->outerHTML($node->getNode(0));

        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }

    public function extractWeatherDetail(string $html, string $upstreamBase): string
    {
        $crawler = new Crawler($html);

        // target: nav.navbar-dark.bg-weather-primary.menu-location
        $node = $crawler->filter('.main-location-heading');

        if ($node->count() === 0) {
            return '';
        }

        $boxHtml = $this->outerHTML($node->getNode(0));

        return $this->rewriteFragmentUrlsKeepPath($boxHtml, $upstreamBase);
    }
}
