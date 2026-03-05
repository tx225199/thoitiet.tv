<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ImageProxyService
{
    /** Whitelist host để tránh biến endpoint thành open proxy */
    protected array $allowedHosts = [
        'static.xoso.com.vn',
        'cdn.xoso.com.vn',
        'xoso.com.vn',
        // thêm host ảnh khác nếu cần
    ];

    public function __construct(protected ?int $timeout = 20)
    {
    }

    public function isAllowedHost(string $host): bool
    {
        $host = strtolower(ltrim($host));
        if (str_starts_with($host, 'www.')) $host = substr($host, 4);
        return in_array($host, $this->allowedHosts, true);
    }

    /**
     * Fetch ảnh từ upstream và trả về mảng [status, headers, body].
     * Có pass-through một số header quan trọng và chấp nhận Range.
     */
    public function fetch(string $host, string $path, Request $request): array
    {
        if (!$this->isAllowedHost($host)) {
            return [403, ['Content-Type' => 'text/plain'], 'Forbidden host'];
        }

        $url = 'https://' . $host . '/' . ltrim($path, '/');

        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
            'Accept'          => '*/*',
            'Accept-Language' => 'vi,en-US;q=0.9',
            'Referer'         => 'https://' . $host . '/',
        ];

        // Range (video/img resume)
        if ($range = $request->header('Range')) {
            $headers['Range'] = $range;
        }
        // Conditional
        if ($ifNoneMatch = $request->header('If-None-Match')) {
            $headers['If-None-Match'] = $ifNoneMatch;
        }
        if ($ifModifiedSince = $request->header('If-Modified-Since')) {
            $headers['If-Modified-Since'] = $ifModifiedSince;
        }

        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout'     => $this->timeout,
        ]);

        /** @var ResponseInterface $res */
        $res = $client->request('GET', $url, [
            'headers' => $headers,
            // Nếu bạn muốn stream trực tiếp có thể dùng ->toStream(), ở đây ta lấy raw content
            'max_redirects' => 3,
        ]);

        $status  = $res->getStatusCode();
        $rawBody = $status === 304 ? '' : $res->getContent(false); // không cần body cho 304

        // Chỉ pass-through các header an toàn/hữu ích
        $upHeaders = $res->getHeaders(false);
        $outHeaders = [];
        $pass = [
            'content-type', 'content-length', 'content-range', 'accept-ranges',
            'cache-control', 'expires', 'etag', 'last-modified', 'date',
        ];
        foreach ($upHeaders as $k => $vals) {
            $lk = strtolower($k);
            if (in_array($lk, $pass, true) && isset($vals[0])) {
                $outHeaders[$k] = $vals[0];
            }
        }

        // Nếu upstream không set cache-control, bạn có thể đặt mặc định:
        if (!array_key_exists('cache-control', array_change_key_case($outHeaders, CASE_LOWER))) {
            $outHeaders['Cache-Control'] = 'public, max-age=86400';
        }

        return [$status, $outHeaders, $rawBody];
    }
}
