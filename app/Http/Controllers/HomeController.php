<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class HomeController extends Controller
{
    protected string $upstream = 'https://thoitiet.tv/';

    public function index(Request $request)
    {

        $settings = Setting::all();

        $arrSettings = array();
        foreach ($settings as $item) {
            $arrSettings[$item->key] = $item->value;
        }

        $service = new SiteService();

        $path   = $request->getRequestUri();
        $target = rtrim($this->upstream, '/') . $path;

        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
            'Accept-Language' => 'vi,en-US;q=0.9',
            'Referer'         => $this->upstream . '/',
        ];

        $doRequest = function (?string $proxy = null) use ($headers, $target) {
            $opts = [
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 12,
                'headers'     => $headers,
            ];
            if ($proxy) $opts['proxy'] = $proxy;

            $client = HttpClient::create($opts);
            $res    = $client->request('GET', $target);

            $st = $res->getStatusCode();
            if (in_array($st, [403, 407, 429], true) || $st >= 500) {
                throw new \RuntimeException("Bad upstream/proxy status: {$st}");
            }
            return $res;
        };

        try {
            $res = null;
            try {
                $res = $doRequest(null);
            } catch (\Throwable $e1) {
                try {
                    $res = $doRequest(getRandomProxy(false));
                } catch (\Throwable $e2) {
                    try {
                        $res = $doRequest(getRandomProxy(true));
                    } catch (\Throwable $e3) {
                        $res = $doRequest(getRandomProxy(false));
                    }
                }
            }

            $html = $res->getContent(false);

            

            return view('site.index', [

            ]);
        } catch (\Throwable $e) {

            dd($e);
        }
    }
}
