<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class XosoAjaxController extends Controller
{
    protected string $upstream = 'https://xoso.com.vn';

    public function forward(Request $request, string $action = '')
    {
        // OPTIONS preflight
        if (strtoupper($request->method()) === 'OPTIONS') {
            return response('', 204)->withHeaders([
                'Access-Control-Allow-Origin'  => url('/'),
                'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            ]);
        }

        // Lấy đúng service từ defaults của route
        $service = $request->route('service', 'ThongKeAjax');

        // Ghép URL upstream
        $remote = rtrim($this->upstream, '/') . '/' . trim($service, '/') . '/' . ltrim($action, '/');
        if ($qs = $request->getQueryString()) {
            $remote .= (str_contains($remote, '?') ? '&' : '?') . $qs;
        }

        $method = strtoupper($request->getMethod());

        // Headers forward
        $headers = array_filter([
            'Accept'           => $request->header('Accept', '*/*'),
            'Accept-Language'  => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
            'Content-Type'     => $request->header('Content-Type'),
            'X-Requested-With' => $request->header('X-Requested-With', 'XMLHttpRequest'),
            'Referer'          => $this->upstream . '/',
        ], static fn($v) => $v !== null && $v !== '');

        $options = [
            'headers'     => $headers,
            'verify_peer' => false,
            'verify_host' => false,
            'timeout'     => 20,
        ];

        // Body các method có payload
        if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
            $ct = strtolower((string)$request->header('Content-Type'));
            if (str_contains($ct, 'application/json')) {
                $options['body'] = $request->getContent(); // raw JSON
            } else {
                $payload = $request->except(['_token']);
                if ($files = $request->allFiles()) {
                    foreach ($files as $key => $file) {
                        if (is_array($file)) continue; // nếu có mảng file, tuỳ nhu cầu flatten thêm
                        $payload[$key] = fopen($file->getRealPath(), 'r');
                    }
                }
                $options['body'] = $payload;
            }
        }

        try {
            $client = HttpClient::create();
            $resp   = $client->request($method, $remote, $options);
            $status = $resp->getStatusCode();
            $body   = $resp->getContent(false);

            $h      = $resp->getHeaders(false);
            $ctype  = $h['content-type'][0]  ?? 'application/json; charset=utf-8';
            $cache  = $h['cache-control'][0] ?? 'no-cache';

            // 🔧 Nếu là HTML → lọc ads rồi mới trả
            if ($this->isHtmlContentType($ctype) && is_string($body) && $body !== '') {
                $body = $this->stripAdsFromHtml($body);
                // đảm bảo Content-Type là text/html khi đã xử lý HTML
                $ctype = 'text/html; charset=UTF-8';
            }

            return response($body, $status)->withHeaders([
                'Content-Type'                => $ctype,
                'Cache-Control'               => $cache,
                'Access-Control-Allow-Origin' => url('/'),
            ]);
        } catch (\Throwable $e) {
            Log::error('XosoAjax forward failed', [
                'service' => $service,
                'action'  => $action,
                'remote'  => $remote ?? null,
                'err'     => $e->getMessage(),
            ]);
            return response()->json(['error' => 'proxy_failed'], 502);
        }
    }

    /** Xác định đây có phải response HTML không */
    protected function isHtmlContentType(string $ctype): bool
    {
        $lc = strtolower($ctype);
        return str_contains($lc, 'text/html') || str_contains($lc, 'application/xhtml');
    }

    /** Lọc quảng cáo trong HTML (ưu tiên dùng clearBySelectors nếu có) */
    protected function stripAdsFromHtml(string $html): string
    {
        try {
            if (function_exists('clearBySelectors')) {
                // ví dụ: '.ads, .adsbygoogle, [class*="ad-"], [id*="ads"]'
                return clearBySelectors($html, '.ads', false);
            }
        } catch (\Throwable $e) {
            Log::debug('clearBySelectors failed', ['err' => $e->getMessage()]);
        }

        // Fallback nhẹ: bỏ các <div class="ads">...</div>
        $out = @preg_replace('#<div\b[^>]*\bclass=("|\')[^"\']*\bads\b[^"\']*\1[^>]*>.*?</div>#is', '', $html);
        return $out !== null ? $out : $html;
    }
}
