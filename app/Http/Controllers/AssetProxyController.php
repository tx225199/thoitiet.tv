<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class AssetProxyController extends Controller
{
    // Map prefix => upstream base
    protected array $map = [
        // CDN ảnh/css/js
        'images' => 'https://cdn.xoso.com.vn/images/',
        'img'    => 'https://cdn.xoso.com.vn/img/',
        'css'    => 'https://cdn.xoso.com.vn/css/',
        'js'     => 'https://cdn.xoso.com.vn/js/',
        'fonts'  => 'https://cdn.xoso.com.vn/fonts/',
        'assets' => 'https://cdn.xoso.com.vn/assets/',
        'cdn'    => 'https://cdn.xoso.com.vn/',

        // Static lớn (ảnh bài viết…)
        'medias' => 'https://static.xoso.com.vn/medias/',
        'static' => 'https://static.xoso.com.vn/',

        // Nội dung khác (nếu có)
        'content' => 'https://xoso.com.vn/content/',
    ];

    public function proxy(Request $request, string $prefix, string $path)
    {
        $pfx = strtolower($prefix);
        if (!isset($this->map[$pfx])) {
            abort(404);
        }

        $base   = rtrim($this->map[$pfx], '/') . '/';
        $remote = $base . ltrim($path, '/');

        // Gắn query string (nếu có) như ?v=...
        if ($qs = $request->getQueryString()) {
            $remote .= (str_contains($remote, '?') ? '&' : '?') . $qs;
        }

        try {
            // Header forward: tránh nén (identity) để khỏi lệch độ dài
            $headers = array_filter([
                'User-Agent'        => $request->userAgent() ?: 'Mozilla/5.0',
                'Accept'            => $request->header('Accept', '*/*'),
                'Accept-Language'   => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
                'Accept-Encoding'   => 'identity', // quan trọng!
                'Referer'           => url('/') . '/',

                // Cache/conditional
                'If-None-Match'     => $request->header('If-None-Match'),
                'If-Modified-Since' => $request->header('If-Modified-Since'),

                // Range/partial
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

            // 304 Not Modified
            if ($status === 304) {
                return response('', 304)->withHeaders($this->pickHeaders($resp->getHeaders(false), [
                    'ETag',
                    'Last-Modified',
                    'Cache-Control',
                    'Expires',
                    'Vary'
                ]));
            }

            // 206 Partial Content (Range)
            if ($status === 206) {
                $body = $resp->getContent(false); // lấy raw decoded
                $h    = $resp->getHeaders(false);

                // TUYỆT ĐỐI KHÔNG set Content-Length/Encoding thủ công
                $out = $this->pickHeaders($h, [
                    'Content-Type',
                    'Content-Range',
                    'Accept-Ranges',
                    'ETag',
                    'Last-Modified',
                    'Cache-Control',
                    'Expires',
                    'Vary'
                ]);
                // đảm bảo Accept-Ranges tồn tại (nếu upstream không set)
                $out['Accept-Ranges'] = $out['Accept-Ranges'] ?? 'bytes';

                return response($body, 206)->withHeaders($out);
            }

            // Lỗi upstream
            if ($status >= 400) {
                Log::warning('Asset proxy upstream error', ['url' => $remote, 'status' => $status]);
                abort(404);
            }

            // 200 OK
            $body = $resp->getContent(false); // không để tự ném exception
            $h    = $resp->getHeaders(false);

            $out = $this->pickHeaders($h, [
                'Content-Type',
                'ETag',
                'Last-Modified',
                'Cache-Control',
                'Expires',
                'Vary',
                'Accept-Ranges'
                // KHÔNG forward Content-Length / Content-Encoding
            ]);
            $out['Accept-Ranges'] = $out['Accept-Ranges'] ?? 'bytes';
            $out['Cache-Control'] = $out['Cache-Control'] ?? 'no-cache';

            return response($body, 200)->withHeaders($out);
        } catch (\Throwable $e) {
            Log::error('Asset proxy failed', ['err' => $e->getMessage(), 'url' => $remote]);
            abort(404);
        }
    }

    /** Chỉ pick những header an toàn cần thiết; tự bỏ null/empty. */
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
