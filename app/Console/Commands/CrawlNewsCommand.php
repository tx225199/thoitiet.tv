<?php

namespace App\Console\Commands;

use App\Jobs\CrawlArticleJob;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class CrawlNewsCommand extends Command
{
    protected $signature = 'news:xoso';
    protected $description = 'Crawl xoso.com.vn: Tin Xổ Số (404), Quy định xổ số (418), Tin trúng thưởng (417) và dispatch từng bài vào queue xskt.';

    public function handle(): int
    {
        $base = 'https://xoso.com.vn';

        // Map cateId -> path trang list p1
        $catePages = [
            404 => '/tin-xo-so-c404-p1.html',
            418 => '/quy-dinh-xo-so-c418-p1.html',
            417 => '/tin-trung-thuong-c417-p1.html',
        ];

        $maxRetries  = 2;
        $dispatched  = 0;
        $seen        = [];

        $toAbs = function (?string $href) use ($base): ?string {
            if (!$href) return null;
            $href = trim($href);
            if ($href === '') return null;
            if (str_starts_with($href, 'data:')) return null;             // bỏ data URL
            if (str_starts_with($href, '//')) return 'https:' . $href;    // protocol-relative
            if (preg_match('#^https?://#i', $href)) return $href;         // absolute
            if (str_starts_with($href, '/')) return rtrim($base, '/') . $href; // root-relative
            return rtrim($base, '/') . '/' . ltrim($href, '/');           // relative
        };

        $getText = fn(Crawler $n, string $sel): ?string =>
            ($n->filter($sel)->count() ? trim($n->filter($sel)->text('')) : null) ?: null;

        $getThumb = function (Crawler $node) use ($toAbs): ?string {
            $img = null;
            foreach (['a.thumb img','.article-image img','picture img','img.image','img'] as $sel) {
                if ($node->filter($sel)->count()) { $img = $node->filter($sel)->first(); break; }
            }
            if (!$img) return null;

            $src = $img->attr('src') ?? null;
            $src = $src && !str_starts_with($src, 'data:image') ? $src : null;
            $src = $src ?: ($img->attr('data-src') ?? null);

            if (!$src) {
                $srcset = $img->attr('data-srcset') ?? $img->attr('srcset');
                if ($srcset) {
                    $best = null; $bestW = -1;
                    foreach (explode(',', $srcset) as $part) {
                        if (preg_match('/(\S+)\s+(\d+)w/', trim($part), $m)) {
                            $u = $m[1]; $w = (int)$m[2];
                            if ($w > $bestW) { $best = $u; $bestW = $w; }
                        }
                    }
                    $src = $best ?: null;
                }
            }
            return $src ? $toAbs($src) : null;
        };

        $dispatch = function (array $item, int $cateId) use (&$seen, &$dispatched) {
            $link = $item['link'] ?? null;
            if (!$link) return;
            if (isset($seen[$link])) return; // chặn trùng liên-cate
            $seen[$link] = 1;

            // chặn spam qua cache 30 phút
            $key = 'dispatch_xoso_' . md5($link);
            if (!cache()->add($key, 1, now()->addMinutes(30))) return;

            // TRUYỀN cateId vào tham số thứ 6
            CrawlArticleJob::dispatch(
                $item['link'],
                $item['title'] ?? null,
                $item['excerpt'] ?? null,
                $item['thumb'] ?? null,
                $item['isVideo'] ?? false,
                $cateId
            )->onQueue('xskt');

            $dispatched++;
            $this->line("→ dispatched: {$link} (cateId={$cateId})");
        };

        foreach ($catePages as $cateId => $path) {
            $url       = $base . $path;
            $retry     = 0;

            $this->info("==> Crawling cateId={$cateId} | {$url}");

            while ($retry < $maxRetries) {
                $proxy = function_exists('getRandomProxy') ? getRandomProxy() : null;

                try {
                    $client = new Client(HttpClient::create([
                        'proxy'        => $proxy,
                        'verify_peer'  => false,
                        'verify_host'  => false,
                        'timeout'      => 25,
                        'headers'      => [
                            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122 Safari/537.36',
                            'Accept-Language' => 'vi,en-US;q=0.9',
                            'Referer'         => $base . '/',
                        ],
                    ]));

                    $crawler = $client->request('GET', $url);

                    // Vùng danh sách
                    $crawler->filter('section.section .section-content .article-list')->each(
                        function (Crawler $node) use ($toAbs, $getText, $getThumb, $dispatch, $cateId) {
                            // Link & title
                            $a = $node->filter('h2.article-title a, h3.article-title a')->first();
                            if (!$a->count()) return;

                            $link   = $toAbs($a->attr('href'));
                            $title  = trim($a->attr('title') ?? '') ?: trim($a->text(''));
                            if (!$link) return;

                            $thumb   = $getThumb($node);
                            $excerpt = $getText($node, '.article-summary');
                            $isVideo = false;

                            $dispatch([
                                'link'    => $link,
                                'title'   => $title ?: null,
                                'excerpt' => $excerpt,
                                'thumb'   => $thumb,
                                'isVideo' => $isVideo,
                            ], (int)$cateId);
                        }
                    );

                    // xong cate hiện tại
                    break;

                } catch (\Throwable $e) {
                    $retry++;
                    Log::warning('Retry xoso list page', [
                        'cateId' => $cateId,
                        'retry'  => $retry,
                        'err'    => $e->getMessage()
                    ]);
                    sleep(1);
                }
            }

            if ($retry >= $maxRetries) {
                Log::error('Crawl list page failed sau nhiều lần retry', [
                    'cateId' => $cateId,
                    'url'    => $url
                ]);
            }
        }

        $this->info("Đã dispatch tổng cộng {$dispatched} bài vào queue 'xskt' cho 3 cate: 404, 418, 417.");
        return self::SUCCESS;
    }
}
