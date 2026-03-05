<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class PathProxyController extends Controller
{
    private function proxyTo(Request $request, string $base, string $path)
    {
        $remote = rtrim($base, '/') . '/' . ltrim($path, '/');

        // giữ query string (v=... cache bust, etc.)
        if ($qs = $request->getQueryString()) {
            $remote .= (str_contains($remote, '?') ? '&' : '?') . $qs;
        }

        try {
            $headers = array_filter([
                'User-Agent'        => $request->userAgent() ?: 'Mozilla/5.0',
                'Accept'            => $request->header('Accept', '*/*'),
                'Accept-Language'   => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
                'Accept-Encoding'   => 'identity', // quan trọng!
                'Referer'           => url('/') . '/',

                'If-None-Match'     => $request->header('If-None-Match'),
                'If-Modified-Since' => $request->header('If-Modified-Since'),

                'Range'             => $request->header('Range'),
                'If-Range'          => $request->header('If-Range'),
            ], fn($v) => $v !== null && $v !== '');

            $client = HttpClient::create([
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 25,
            ]);

            $resp   = $client->request('GET', $remote, ['headers' => $headers]);
            $status = $resp->getStatusCode();

            // 304
            if ($status === 304) {
                return response('', 304)->withHeaders($this->pickHeaders($resp->getHeaders(false), [
                    'ETag', 'Last-Modified', 'Cache-Control', 'Expires', 'Vary'
                ]));
            }

            // 206 (Range)
            if ($status === 206) {
                $body = $resp->getContent(false);
                $h    = $resp->getHeaders(false);

                $out = $this->pickHeaders($h, [
                    'Content-Type','Content-Range','Accept-Ranges','ETag','Last-Modified','Cache-Control','Expires','Vary'
                ]);
                $out['Accept-Ranges'] = $out['Accept-Ranges'] ?? 'bytes';

                return response($body, 206)->withHeaders($out);
            }

            if ($status >= 400) {
                Log::warning('Path proxy upstream error', ['url' => $remote, 'status' => $status]);
                abort(404);
            }

            // 200
            $body = $resp->getContent(false);
            $h    = $resp->getHeaders(false);

            $out = $this->pickHeaders($h, [
                'Content-Type','ETag','Last-Modified','Cache-Control','Expires','Vary','Accept-Ranges'
            ]);
            $out['Accept-Ranges'] = $out['Accept-Ranges'] ?? 'bytes';
            $out['Cache-Control'] = $out['Cache-Control'] ?? 'public, max-age=600';

            return response($body, 200)->withHeaders($out);
        } catch (\Throwable $e) {
            Log::error('Path proxy failed', ['err' => $e->getMessage(), 'url' => $remote]);
            abort(404);
        }
    }

    // cdn.weatherapi.com => /weather/...
    public function weatherapi(Request $request, string $path)
    {
        return $this->proxyTo($request, 'https://cdn.weatherapi.com/weather/', $path);
    }

    // thoitiet.tv => giữ nguyên path
    public function thoitietThemes(Request $request, string $path) { return $this->proxyTo($request, 'https://thoitiet.tv/themes/', $path); }
    public function thoitietCss(Request $request, string $path)    { return $this->proxyTo($request, 'https://thoitiet.tv/css/', $path); }
    public function thoitietJs(Request $request, string $path)     { return $this->proxyTo($request, 'https://thoitiet.tv/js/', $path); }
    public function thoitietImages(Request $request, string $path) { return $this->proxyTo($request, 'https://thoitiet.tv/images/', $path); }
    public function thoitietImg(Request $request, string $path)    { return $this->proxyTo($request, 'https://thoitiet.tv/img/', $path); }
    public function thoitietFonts(Request $request, string $path)  { return $this->proxyTo($request, 'https://thoitiet.tv/fonts/', $path); }
    public function thoitietAssets(Request $request, string $path) { return $this->proxyTo($request, 'https://thoitiet.tv/assets/', $path); }

    private function pickHeaders(array $src, array $allow): array
    {
        $out = [];
        $allowL = array_map('strtolower', $allow);
        foreach ($src as $name => $vals) {
            if (in_array(strtolower($name), $allowL, true) && !empty($vals)) {
                $out[$name] = end($vals);
            }
        }
        return $out;
    }
}