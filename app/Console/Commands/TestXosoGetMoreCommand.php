<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class TestXosoGetMoreCommand extends Command
{
    protected $signature = 'test:xoso:getmore';
    protected $description = 'Test gọi https://xoso.com.vn/TinTuc/GetMore?pageIndex=1&cateId=404 và in dữ liệu parse được.';

    public function handle(): int
    {

        $proxy = function_exists('getRandomProxy') ? getRandomProxy() : null;

        dd($proxy);

        $url = 'https://xoso.com.vn/TinTuc/GetMore?pageIndex=30&cateId=404';
        $ref = 'https://xoso.com.vn/tin-xo-so-c404-p1.html';

        $http = HttpClient::create([
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
            'headers'     => [
                'Accept'           => '*/*',
                'X-Requested-With' => 'XMLHttpRequest',
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140 Safari/537.36',
                'Referer'          => $ref,
                'Accept-Language'  => 'vi,en-US;q=0.9,en;q=0.8',
            ],
        ]);

        try {
            $resp   = $http->request('GET', $url);
            $status = $resp->getStatusCode();
            $html   = trim($resp->getContent(false));

            $this->info("HTTP {$status}, length=" . strlen($html));

            if ($status !== 200 || $html === '') {
                $this->warn(substr($html, 0, 300));
                return self::SUCCESS;
            }

            $crawler = new Crawler('<div id="root">'.$html.'</div>');
            $items   = $crawler->filter('#root article.article-list');

            $this->line('Found: ' . $items->count() . ' articles');

            $items->slice(0, 5)->each(function ($node, $i) {
                $a = $node->filter('header a')->first();
                $title = trim($a->attr('title') ?: $a->text(''));
                $href  = trim($a->attr('href') ?? '');
                $img   = $node->filter('img')->count() ? $node->filter('img')->first()->attr('src') : null;
                $sum   = $node->filter('.article-summary')->count()
                    ? trim($node->filter('.article-summary')->text('')) : null;

                $this->info(($i + 1) . ". {$title}");
                $this->line("   Link : {$href}");
                if ($img) $this->line("   Img  : {$img}");
                if ($sum) $this->line("   Desc : " . mb_strimwidth($sum, 0, 120, '…'));
            });

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return self::SUCCESS;
    }
}
