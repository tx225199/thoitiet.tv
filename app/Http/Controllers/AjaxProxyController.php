<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class AjaxProxyController extends Controller
{
    protected string $upstream = 'https://xoso.com.vn';

    public function proxy(Request $request, string $path = '')
    {
        // Ghép URL upstream đầy đủ: https://xoso.com.vn/{path}?{query}
        $remote = rtrim($this->upstream, '/') . '/' . ltrim($path, '/');
        if ($request->getQueryString()) {
            $remote .= '?' . $request->getQueryString();
        }

        try {
            $method  = strtoupper($request->getMethod());

            // Chuẩn hoá headers cho upstream (lọc những cái nên forward)
            $forwardHeaders = [
                'Accept'          => $request->header('Accept', '*/*'),
                'Accept-Language' => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
                'Content-Type'    => $request->header('Content-Type'),
                'X-Requested-With'=> $request->header('X-Requested-With', 'XMLHttpRequest'),
                'Referer'         => url($request->path()), // tham khảo, không bắt buộc
                // ETag/Last-Modified
                'If-None-Match'     => $request->header('If-None-Match'),
                'If-Modified-Since' => $request->header('If-Modified-Since'),
            ];
            $headers = array_filter($forwardHeaders, fn($v) => !is_null($v) && $v !== '');

            // Body (hỗ trợ json/form/file)
            $options = [
                'headers'     => $headers,
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 20,
            ];

            if (in_array($method, ['POST','PUT','PATCH','DELETE'])) {
                // Nếu là JSON
                if (str_contains(strtolower((string)$request->header('Content-Type')), 'application/json')) {
                    $options['body'] = $request->getContent();
                }
                // Nếu là form-data / x-www-form-urlencoded (kể cả file)
                else {
                    // Symfony HttpClient sẽ tự encode multipart nếu truyền mảng
                    $payload = $request->all();

                    // Đính kèm files (nếu có)
                    if (count($request->allFiles())) {
                        foreach ($request->allFiles() as $key => $file) {
                            if (is_array($file)) continue; // tuỳ trường hợp, có thể flatten thêm
                            $payload[$key] = fopen($file->getRealPath(), 'r');
                        }
                    }
                    $options['body'] = $payload;
                }
            }

            $client = HttpClient::create();
            $resp   = $client->request($method, $remote, $options);
            $status = $resp->getStatusCode();

            // 304 passthrough
            if ($status === 304) {
                return response('', 304)->withHeaders($this->pickHeaders($resp->getHeaders(false), [
                    'ETag','Last-Modified','Cache-Control','Expires'
                ]));
            }

            // Lỗi upstream → 502 cho rõ nghĩa (hoặc 404 nếu muốn "ẩn")
            if ($status >= 400) {
                Log::warning('AJAX proxy upstream error', ['url' => $remote, 'status' => $status]);
                return response('Upstream error', 502);
            }

            $body = $resp->getContent(false);

            // Forward một số header an toàn
            $outHeaders = $this->pickHeaders($resp->getHeaders(false), [
                'Content-Type','Content-Length','ETag','Last-Modified','Cache-Control','Expires'
            ]);

            // Cho phép cache vừa phải nếu upstream không set
            if (!isset($outHeaders['Cache-Control'])) {
                $outHeaders['Cache-Control'] = 'no-cache';
            }

            return response($body, $status)->withHeaders($outHeaders);

        } catch (\Throwable $e) {
            Log::error('AJAX proxy failed', ['err' => $e->getMessage(), 'url' => $remote]);
            return response('Proxy error', 502);
        }
    }

    private function pickHeaders(array $upstreamHeaders, array $allow): array
    {
        $out = [];
        $allowL = array_map('strtolower', $allow);
        foreach ($upstreamHeaders as $name => $vals) {
            if (in_array(strtolower($name), $allowL, true)) {
                $out[$name] = end($vals);
            }
        }
        return $out;
    }
}
