<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class AjaxSearchController extends Controller
{
    protected string $upstream = 'https://thoitiet.tv';

    public function search(Request $request)
    {
        $searchTerm = (string) $request->query('searchTerm', '');
        $type       = (string) $request->query('type', '2');

        // optional: chặn query rỗng để giảm load
        if (trim($searchTerm) === '') {
            return response()->json(['html' => '']);
        }

        $url = rtrim($this->upstream, '/') . '/ajax/search?' . http_build_query([
            'searchTerm' => $searchTerm,
            'type'       => $type,
        ]);

        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout'     => 12,
            'headers'     => [
                'User-Agent'      => $request->userAgent() ?: 'Mozilla/5.0',
                'Accept'          => '*/*',
                'Accept-Language' => $request->header('Accept-Language', 'vi,en-US;q=0.9'),
                'Referer'         => rtrim($this->upstream, '/') . '/',
                'X-Requested-With'=> 'XMLHttpRequest',
            ],
        ]);

        $res = $client->request('GET', $url);
        $st  = $res->getStatusCode();

        if ($st >= 400) {
            // fallback an toàn
            return response()->json(['html' => '']);
        }

        $body = $res->getContent(false);

        // upstream trả JSON dạng: {"html":"..."}
        $json = json_decode($body, true);

        if (!is_array($json) || !array_key_exists('html', $json)) {
            return response()->json(['html' => '']);
        }

        // trả đúng format cho JS hiện tại
        return response()->json([
            'html' => (string) $json['html'],
        ]);
    }
}