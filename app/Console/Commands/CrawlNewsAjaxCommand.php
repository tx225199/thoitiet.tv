<?php

namespace App\Console\Commands;

use App\Jobs\CrawlArticleJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class CrawlNewsAjaxCommand extends Command
{
    // http://tombxtb5S:XLtUVy2r@103.183.119.19:8196
    // http://tombFyST8:FIL7bEUW@103.183.119.19:8290

    protected $signature   = 'news:xoso:ajax 
                                {--cate=* : Danh sách cateId (vd: 404 418 417)} 
                                {--from=30 : Bắt đầu từ page} 
                                {--to=1 : Kết thúc ở page} 
                                {--sleep=250 : nghỉ (ms) giữa mỗi request}';
    protected $description = 'Crawl tin từ xoso.com.vn qua endpoint /TinTuc/GetMore cho các cateId (404 Tin xổ số, 418 Quy định, 417 Tin trúng thưởng).';

    public function handle(): int
    {
        $base   = 'https://xoso.com.vn';
        $apiTpl = $base . '/TinTuc/GetMore?pageIndex=%d&cateId=%d';

        // cateId mặc định nếu không truyền option
        $cateIds = $this->option('cate');
        if (empty($cateIds)) {
            $cateIds = [404, 418, 417];
        }
        $cateIds = array_values(array_unique(array_map('intval', $cateIds)));

        $from   = max(1, (int) $this->option('from') ?: 30);
        $to     = max(1, (int) $this->option('to') ?: 1);
        if ($from < $to) {
            [$from, $to] = [$to, $from]; // đảm bảo chạy giảm dần
        }
        $sleepMs = max(0, (int) $this->option('sleep') ?: 250);

        $toAbs = function (?string $href) use ($base) {
            if (!$href) return null;
            $href = trim($href);
            if ($href === '' || str_starts_with($href, 'javascript:')) return null;
            if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) return $href;
            if (str_starts_with($href, '//')) return 'https:' . $href;
            if (str_starts_with($href, '/')) return rtrim($base, '/') . $href;
            return rtrim($base, '/') . '/' . ltrim($href, '/');
        };

        $pickThumb = function (Crawler $node) {
            if ($node->filter('img')->count()) {
                $img = $node->filter('img')->first();
                $src = $img->attr('src') ?: $img->attr('data-src') ?: $img->attr('data-original');
                return $src ?: null;
            }
            return null;
        };

        $http = HttpClient::create([
            'timeout'     => 20,
            'verify_peer' => false,
            'verify_host' => false,
            'headers'     => [
                'Accept'           => '*/*',
                'X-Requested-With' => 'XMLHttpRequest',
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140 Safari/537.36',
                'Accept-Language'  => 'vi,en-US;q=0.9,en;q=0.8',
            ],
        ]);

        $totalAll = 0;

        foreach ($cateIds as $cateId) {
            $this->line("=== Cate {$cateId}: pages {$from} → {$to} ===");

            $totalCate = 0;

            for ($page = $from; $page >= $to; $page--) {
                $url = sprintf($apiTpl, $page, $cateId);

                try {
                    // Thêm Referer đúng chuyên mục để “giống trình duyệt”
                    $ref = match ($cateId) {
                        404 => $base . '/tin-xo-so-c404-p1.html',
                        418 => $base . '/quy-dinh-xo-so-c418-p1.html',
                        417 => $base . '/tin-trung-thuong-c417-p1.html',
                        default => $base . '/',
                    };

                    $resp = $http->request('GET', $url, [
                        'headers' => ['Referer' => $ref],
                    ]);

                    $status = $resp->getStatusCode();
                    if ($status !== 200) {
                        $this->warn("cate={$cateId} page={$page} HTTP {$status}, bỏ qua.");
                        usleep($sleepMs * 1000);
                        continue;
                    }

                    $html = (string) $resp->getContent(false);
                    $html = trim($html);

                    if ($html === '') {
                        $this->warn("cate={$cateId} page={$page} rỗng.");
                        usleep($sleepMs * 1000);
                        continue;
                    }

                    // Bọc div để DomCrawler có root node
                    $crawler = new Crawler('<div id="root">'.$html.'</div>');
                    $items   = $crawler->filter('#root article.article-list');

                    if (!$items->count()) {
                        $this->warn("cate={$cateId} page={$page} không tìm thấy article.");
                        usleep($sleepMs * 1000);
                        continue;
                    }

                    $count = 0;

                    $items->each(function (Crawler $node) use (&$count, $toAbs, $pickThumb, $cateId) {
                        // link + title
                        $aNode = $node->filter('header h2 a, header h3 a, h2.article-title a, h3.article-title a')->first();
                        if (!$aNode->count()) return;

                        $href  = $toAbs($aNode->attr('href'));
                        $title = trim($aNode->attr('title') ?: $aNode->text(''));

                        if (!$href) return;

                        // tóm tắt
                        $excerpt = null;
                        if ($node->filter('.article-summary')->count()) {
                            $excerpt = trim($node->filter('.article-summary')->text(''));
                        }

                        // ảnh
                        $thumbRaw = $pickThumb($node) ?: null;
                        $thumb    = $thumbRaw ? $toAbs($thumbRaw) : null;

                        // DISPATCH JOB — TRUYỀN cateId VÀO THAM SỐ THỨ 6
                        CrawlArticleJob::dispatch($href, $title, $excerpt, $thumb, false, (int) $cateId)
                            ->onQueue('xskt');

                        $count++;
                    });

                    $this->info("cate={$cateId} page={$page}: dispatched {$count} bài.");
                    $totalCate  += $count;
                    $totalAll   += $count;

                    usleep($sleepMs * 1000);

                } catch (\Throwable $e) {
                    Log::error('Crawl Xoso GetMore failed', [
                        'cate' => $cateId,
                        'page' => $page,
                        'err'  => $e->getMessage(),
                    ]);
                    // tiếp tục trang kế tiếp
                }
            }

            $this->line("— Cate {$cateId}: tổng dispatched {$totalCate} bài.");
        }

        $this->info("Hoàn tất. Tổng cộng dispatched {$totalAll} bài cho các cate: ".implode(',', $cateIds).'.');
        return self::SUCCESS;
    }
}
