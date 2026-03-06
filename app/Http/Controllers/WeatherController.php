<?php

namespace App\Http\Controllers;

use App\Services\SiteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class WeatherController extends Controller
{
    protected string $upstream = 'https://thoitiet.tv/';

    public function show(Request $request, string $citySlug)
    {
        $service = new SiteService();

        // build path + query (giữ query, bỏ debug)
        $path = '/' . ltrim($citySlug, '/');

        $q = $request->query();
        unset($q['XDEBUG_SESSION_START'], $q['_debugbar'], $q['_']);

        $target      = rtrim($this->upstream, '/') . $path . ($q ? ('?' . http_build_query($q)) : '');
        $cleanTarget = rtrim($this->upstream, '/') . $path;

        $headers = [
            'User-Agent'      => $request->userAgent() ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
            'Accept-Language' => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
            'Accept-Encoding' => 'identity',
            'Referer'         => rtrim($this->upstream, '/') . '/',
        ];

        $doRequest = function (string $url) use ($headers) {
            $client = HttpClient::create([
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 12,
                'headers'     => $headers,
            ]);

            $res = $client->request('GET', $url);
            $st  = $res->getStatusCode();

            if (in_array($st, [403, 407, 429], true) || $st >= 500) {
                throw new \RuntimeException("Bad upstream status: {$st}");
            }

            return $res;
        };

        try {
            try {
                $res = $doRequest($target);
            } catch (\Throwable $e) {
                $res = $doRequest($cleanTarget);
            }

            $html = $res->getContent(false);

            $headMeta = $service->extractHeadMeta($html, rtrim($this->upstream, '/'));
            $breadcrumbHtml = $service->extractCityBreadcrumb($html, rtrim($this->upstream, '/'));
            $tabsNavHtml = $service->extractCityTabsNav($html, rtrim($this->upstream, '/'));
            $weatherDetail = $service->extractWeatherDetail($html, rtrim($this->upstream, '/'));

            return view('site.weather', [
                'citySlug' => $citySlug,
                'headMeta' => $headMeta,
                'breadcrumbHtml' => $breadcrumbHtml,
                'tabsNavHtml' => $tabsNavHtml,
                'weatherDetail' => $weatherDetail
            ]);
        } catch (\Throwable $e) {
            dd($e);
        }
    }
}
