<?php

namespace App\Http\Controllers;

use App\Services\SiteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class HomeController extends Controller
{
    protected string $upstream = 'https://thoitiet.tv/';

    public function index(Request $request)
    {
        $service = new SiteService();

        $path = $request->getPathInfo();

        $q = $request->query();
        unset($q['XDEBUG_SESSION_START'], $q['_debugbar'], $q['_']);

        $target      = rtrim($this->upstream, '/') . $path . ($q ? ('?' . http_build_query($q)) : '');
        $cleanTarget = rtrim($this->upstream, '/') . $path;

        $headers = [
            'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
            'Accept-Language'   => 'vi,en-US;q=0.9',
            'Accept-Encoding'   => 'identity',
            'Referer'           => rtrim($this->upstream, '/') . '/',
        ];

        $doRequest = function (?string $proxy, string $url) use ($headers) {
            $opts = [
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 12,
                'headers'     => $headers,
            ];
            if ($proxy) $opts['proxy'] = $proxy;

            $client = HttpClient::create($opts);
            $res    = $client->request('GET', $url);

            $st = $res->getStatusCode();

            // 403/407/429/5xx => throw để fallback
            if (in_array($st, [403, 407, 429], true) || $st >= 500) {
                throw new \RuntimeException("Bad upstream/proxy status: {$st}");
            }

            return $res;
        };

        try {
            try {
                $res = $doRequest(null, $target);
            } catch (\Throwable $e) {
                $res = $doRequest(null, $cleanTarget);
            }

            $html = $res->getContent(false);

            $boxCurrentWeather  = $service->extractBoxCurrentWeather($html, $this->upstream);
            $boxFeaturedWeather = $service->extractBoxFeaturedWeather($html, $this->upstream);

            $lat = session('client_lat', 21.033);
            $lng = session('client_lng', 105.833);

            return view('site.index', [
                'boxCurrentWeather' => $boxCurrentWeather,
                'boxFeaturedWeather' => $boxFeaturedWeather,
                'windyLat'           => $lat,
                'windyLng'           => $lng,
            ]);
        } catch (\Throwable $e) {
            dd($e);
        }
    }
}
