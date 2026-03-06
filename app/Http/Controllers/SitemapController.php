<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Genre;
use Carbon\Carbon;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private string $base;
    private int $maxUrlsAllowed = 50000;

    public function __construct()
    {
        $this->base = rtrim(config('app.url'), '/');
    }

    public function index(): Response
    {
        $urls = [];

        // Static pages
        $urls[] = $this->makeUrl($this->abs('/'), now(), 'hourly', '1.0');
        $urls[] = $this->makeUrl($this->abs('/lien-he'), now(), 'monthly', '0.6');

        // Genres
        Genre::query()
            ->where('slug', '!=', '')
            ->orderByDesc('updated_at')
            ->chunkById(200, function ($genres) use (&$urls) {
                foreach ($genres as $genre) {
                    $urls[] = $this->makeUrl(
                        $this->abs('/muc/' . $this->e($genre->slug)),
                        $genre->updated_at ?? $genre->created_at ?? now(),
                        'daily',
                        '0.7'
                    );
                }
            });

        // Articles
        Article::query()
            ->where('slug', '!=', '')
            ->when(method_exists(Article::class, 'scopePublished'), function ($query) {
                $query->published();
            })
            ->orderByDesc('published_at')
            ->chunkById(500, function ($articles) use (&$urls) {
                foreach ($articles as $article) {
                    $lastmod = $article->updated_at ?? $article->published_at ?? $article->created_at ?? now();

                    $urls[] = $this->makeUrl(
                        $this->abs('/tin/' . $this->e($article->slug)),
                        $lastmod,
                        $this->changefreqFor($lastmod),
                        '0.8'
                    );
                }
            });

        if (count($urls) > $this->maxUrlsAllowed) {
            $urls = array_slice($urls, 0, $this->maxUrlsAllowed);
        }

        $xml = $this->buildUrlsetXml($urls);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function abs(string $path): string
    {
        return $this->base . '/' . ltrim($path, '/');
    }

    private function e(string $segment): string
    {
        return rawurlencode($segment);
    }

    private function makeUrl(string $loc, $lastmod = null, string $changefreq = 'daily', string $priority = '0.8'): array
    {
        return [
            'loc'        => htmlspecialchars($loc, ENT_XML1, 'UTF-8'),
            'lastmod'    => Carbon::parse($lastmod ?? now())->toDateString(),
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }

    private function changefreqFor($lastmod): string
    {
        $days = Carbon::parse($lastmod)->diffInDays(now());

        if ($days <= 1) {
            return 'hourly';
        }

        if ($days <= 7) {
            return 'daily';
        }

        if ($days <= 30) {
            return 'weekly';
        }

        return 'monthly';
    }

    private function buildUrlsetXml(array $urls): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $url['loc'] . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }
}