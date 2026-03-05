<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class HomeController extends Controller
{
    protected string $upstream = 'https://xoso.com.vn';

    // Các host asset sẽ được rewrite về domain hiện tại
    protected array $assetHosts = [
        'static.xoso.com.vn',
        'cdn.xoso.com.vn',
        'xoso.com.vn',
    ];

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

            // ==== lấy phần tử ====
            $headStyles  = $service->extractHeadStyles($html);
            $nav         = $service->extractNavHeader($html);
            $nav         = $service->injectGenresIntoNav($nav);
            $navMobile   = $service->extractNavMobile($html);
            $navMobile   = $service->injectMobileGenresIntoNav($navMobile);
            $navMobile   = $service->replaceMobileSidebarLogo($navMobile, $arrSettings);

            $tailScripts = $service->extractScriptsAfterJsAll($html);

            // mới: content-left + 2 aside
            $main       = $service->extractContentLeft($html);
            $aside160   = $service->extractAside160($html);
            $aside300   = $service->extractAside300($html);

            // ==== clean/convert ====
            $main     = $service->fixLazyAttrs($main);
            $main     = $service->rewriteLinksXoso($main);
            $main     = $service->stripScripts($main);

            $aside160 = $service->fixLazyAttrs($aside160);
            $aside160 = $service->rewriteLinksXoso($aside160);
            $aside160 = $service->stripScripts($aside160);

            $aside300 = $service->fixLazyAttrs($aside300);
            $aside300 = $service->rewriteLinksXoso($aside300);
            $aside300 = $service->stripScripts($aside300);

            $nav      = $service->stripScripts($nav);
            $navMobile = $service->stripScripts($navMobile);

            $breadcrumb = $service->extractBreadcrumb($html);

            $breadcrumb = $service->stripScripts($breadcrumb);

            if (function_exists('clearBySelectors')) {
                $main     = clearBySelectors($main,     '.ads', false);
                $aside160 = clearBySelectors($aside160, '.ads', false);
                $aside300 = clearBySelectors($aside300, '.ads', false);
                $nav      = clearBySelectors($nav,      '.ads', false);
            }

            // ==== rewrite asset host ====
            $base       = rtrim(url('/'), '/');
            $main       = rewriteHtmlAssetsToLocalDomain($main,     $this->assetHosts, $base);
            $aside160   = rewriteHtmlAssetsToLocalDomain($aside160, $this->assetHosts, $base);
            $aside300   = rewriteHtmlAssetsToLocalDomain($aside300, $this->assetHosts, $base);
            $nav        = rewriteHtmlAssetsToLocalDomain($nav,      $this->assetHosts, $base);
            $navMobile  = rewriteHtmlAssetsToLocalDomain($navMobile, $this->assetHosts, $base);
            $breadcrumb = rewriteHtmlAssetsToLocalDomain($breadcrumb, $this->assetHosts, $base);

            $headStyles  = rewriteCssUrlsToLocalDomain($headStyles, $this->assetHosts, $base);
            $tailScripts = $service->rewriteScriptSrcsToLocalDomain($tailScripts, $this->assetHosts, $base);

            // ==== ajax + bust cache version ====
            $main       = rewriteInlineAjaxCalls($main, '/ajax');
            $aside160   = rewriteInlineAjaxCalls($aside160, '/ajax');
            $aside300   = rewriteInlineAjaxCalls($aside300, '/ajax');

            $main       = $service->bustAssetVersion($main);
            $aside160   = $service->bustAssetVersion($aside160);
            $aside300   = $service->bustAssetVersion($aside300);
            $tailScripts = $service->bustAssetVersion($tailScripts);

            // ==== SEO ====
            $metaSeo     = $service->extractMetaSeoAndRewrite($html, config('app.domain'));
            [$main, $metaSeo] = $service->replacePhrasesEverywhere($main, $metaSeo);

            return view('site.index', [
                'main'        => $main,
                'aside160'    => $aside160,
                'aside300'    => $aside300,
                'headStyles'  => $headStyles,
                'nav'         => $nav,
                'tailScripts' => $tailScripts,
                'metaSeo'     => $metaSeo,
                'navMobile'   => $navMobile,
                'breadcrumb'  => $breadcrumb
            ]);
        } catch (\Throwable $e) {
            Log::error('Mirror xoso failed', ['err' => $e->getMessage(), 'url' => $target]);

            return view('site.index', [
                'main'        => '<div class="pad">Không tải được nội dung. Vui lòng thử lại sau.</div>',
                'aside160'    => '',
                'aside300'    => '',
                'headStyles'  => '',
                'nav'         => '',
                'tailScripts' => '',
            ]);
        }
    }
}
