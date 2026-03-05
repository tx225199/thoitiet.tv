<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Article;
use App\Models\Setting;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;
use Illuminate\Support\Str;


class PageController extends Controller
{
    protected string $upstream = 'https://xoso.com.vn';

    // Các host asset sẽ được rewrite về domain hiện tại
    protected array $assetHosts = [
        'static.xoso.com.vn',
        'cdn.xoso.com.vn',
        'xoso.com.vn',
    ];

    public function genre(Request $request, $slug)
    {

        $settings = Setting::all();

        $arrSettings = array();
        foreach ($settings as $item) {
            $arrSettings[$item->key] = $item->value;
        }

        $service = new SiteService();

        // $path   = $request->getRequestUri();
        $path = '/tin-xo-so-c404-p1.html';
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
            $tailScripts = $service->extractScriptsAfterJsAll($html);


            $aside160   = $service->extractAside160($html);
            $aside300   = $service->extractAside300($html);

            $aside160 = $service->fixLazyAttrs($aside160);
            $aside160 = $service->rewriteLinksXoso($aside160);
            $aside160 = $service->stripScripts($aside160);

            $aside300 = $service->fixLazyAttrs($aside300);
            $aside300 = $service->rewriteLinksXoso($aside300);
            $aside300 = $service->stripScripts($aside300);

            $nav         = $service->extractNavHeader($html);
            $nav         = $service->injectGenresIntoNav($nav);
            $navMobile   = $service->extractNavMobile($html);
            $navMobile   = $service->injectMobileGenresIntoNav($navMobile);
            $navMobile   = $service->replaceMobileSidebarLogo($navMobile, $arrSettings);

            $breadcrumb = $service->extractBreadcrumb($html);

            $breadcrumb = $service->stripScripts($breadcrumb);

            if (function_exists('clearBySelectors')) {
                $aside160 = clearBySelectors($aside160, '.ads', false);
                $aside300 = clearBySelectors($aside300, '.ads', false);
                $nav      = clearBySelectors($nav,      '.ads', false);
            }

            // ==== rewrite asset host ====
            $base       = rtrim(url('/'), '/');
            $aside160   = rewriteHtmlAssetsToLocalDomain($aside160, $this->assetHosts, $base);
            $aside300   = rewriteHtmlAssetsToLocalDomain($aside300, $this->assetHosts, $base);
            $nav        = rewriteHtmlAssetsToLocalDomain($nav,      $this->assetHosts, $base);
            $navMobile  = rewriteHtmlAssetsToLocalDomain($navMobile, $this->assetHosts, $base);
            $breadcrumb = rewriteHtmlAssetsToLocalDomain($breadcrumb, $this->assetHosts, $base);

            $headStyles  = rewriteCssUrlsToLocalDomain($headStyles, $this->assetHosts, $base);
            $tailScripts = $service->rewriteScriptSrcsToLocalDomain($tailScripts, $this->assetHosts, $base);

            // ==== ajax + bust cache version ====
            $aside160   = rewriteInlineAjaxCalls($aside160, '/ajax');
            $aside300   = rewriteInlineAjaxCalls($aside300, '/ajax');

            $aside160   = $service->bustAssetVersion($aside160);
            $aside300   = $service->bustAssetVersion($aside300);
            $tailScripts = $service->bustAssetVersion($tailScripts);

            // ==== SEO ====
            $metaSeo     = $service->extractMetaSeoAndRewrite($html, config('app.domain'));
            [$main, $metaSeo] = $service->replacePhrasesEverywhere('', $metaSeo);

            $genre = Genre::where('slug', $slug)->where('hidden', 0)->firstOrFail();

            $perPage = (int) $request->get('pageSize', 10);
            $page    = (int) $request->get('page', 1);

            // Bài top lớn nhất
            $topBig = Article::with('genre:id,slug,name')
                ->published()
                ->where('genre_id', $genre->id)
                ->orderByDesc('highlight')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

            // 4 bài nhỏ kế tiếp (loại trừ topBig)
            $exclude = $topBig ? [$topBig->id] : [];
            $topSmall4 = Article::with('genre:id,slug,name')
                ->published()
                ->where('genre_id', $genre->id)
                ->whereNotIn('id', $exclude)
                ->orderByDesc('highlight')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(10)
                ->get();

            $excludeIds = collect([$topBig])->filter()->pluck('id')
                ->merge($topSmall4->pluck('id'))
                ->all();

            // Danh sách phân trang (loại trừ các bài đã show)
            $paginator = Article::with('genre:id,slug,name')
                ->published()
                ->where('genre_id', $genre->id)
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('highlight')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            if ($request->ajax() || $request->get('ajax')) {
                $html = view('site.partials._article_horizontal', [
                    'items' => $paginator->items(),
                    'genre' => $genre,
                ])->render();

                return response()->json([
                    'status'   => true,
                    'html'     => $html,
                    'hasMore'  => $paginator->hasMorePages(),
                    'nextPage' => $paginator->currentPage() + 1,
                ]);
            }

            return view('site.genre', [
                'aside160'    => $aside160,
                'aside300'    => $aside300,
                'headStyles'  => $headStyles,
                'nav'         => $nav,
                'tailScripts' => $tailScripts,
                'metaSeo'     => $metaSeo,
                'navMobile'   => $navMobile,
                'breadcrumb'  => $breadcrumb,

                'genre'      => $genre,
                'topBig'     => $topBig,
                'topSmall4'  => $topSmall4,
                'list'       => $paginator,
            ]);
        } catch (\Throwable $e) {
            Log::error('Mirror xoso failed', ['err' => $e->getMessage(), 'url' => $target]);

            return view('site.genre', [
                'main'        => '<div class="pad">Không tải được nội dung. Vui lòng thử lại sau.</div>',
                'aside160'    => '',
                'aside300'    => '',
                'headStyles'  => '',
                'nav'         => '',
                'tailScripts' => '',
            ]);
        }
    }


    public function article(Request $request, $slug)
    {
        $settings = Setting::all();

        $arrSettings = array();
        foreach ($settings as $item) {
            $arrSettings[$item->key] = $item->value;
        }

        $service = new SiteService();

        // $path   = $request->getRequestUri();
        $path = '/tin-xo-so-c404-p1.html';
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
            $tailScripts = $service->extractScriptsAfterJsAll($html);


            $aside160   = $service->extractAside160($html);
            $aside300   = $service->extractAside300($html);

            $aside160 = $service->fixLazyAttrs($aside160);
            $aside160 = $service->rewriteLinksXoso($aside160);
            $aside160 = $service->stripScripts($aside160);

            $aside300 = $service->fixLazyAttrs($aside300);
            $aside300 = $service->rewriteLinksXoso($aside300);
            $aside300 = $service->stripScripts($aside300);

            $nav         = $service->extractNavHeader($html);
            $nav         = $service->injectGenresIntoNav($nav);
            $navMobile   = $service->extractNavMobile($html);
            $navMobile   = $service->injectMobileGenresIntoNav($navMobile);
            $navMobile   = $service->replaceMobileSidebarLogo($navMobile, $arrSettings);

            $breadcrumb = $service->extractBreadcrumb($html);

            $breadcrumb = $service->stripScripts($breadcrumb);

            if (function_exists('clearBySelectors')) {
                $aside160 = clearBySelectors($aside160, '.ads', false);
                $aside300 = clearBySelectors($aside300, '.ads', false);
                $nav      = clearBySelectors($nav,      '.ads', false);
            }

            // ==== rewrite asset host ====
            $base       = rtrim(url('/'), '/');
            $aside160   = rewriteHtmlAssetsToLocalDomain($aside160, $this->assetHosts, $base);
            $aside300   = rewriteHtmlAssetsToLocalDomain($aside300, $this->assetHosts, $base);
            $nav        = rewriteHtmlAssetsToLocalDomain($nav,      $this->assetHosts, $base);
            $navMobile  = rewriteHtmlAssetsToLocalDomain($navMobile, $this->assetHosts, $base);
            $breadcrumb = rewriteHtmlAssetsToLocalDomain($breadcrumb, $this->assetHosts, $base);

            $headStyles  = rewriteCssUrlsToLocalDomain($headStyles, $this->assetHosts, $base);
            $tailScripts = $service->rewriteScriptSrcsToLocalDomain($tailScripts, $this->assetHosts, $base);

            // ==== ajax + bust cache version ====
            $aside160   = rewriteInlineAjaxCalls($aside160, '/ajax');
            $aside300   = rewriteInlineAjaxCalls($aside300, '/ajax');

            $aside160   = $service->bustAssetVersion($aside160);
            $aside300   = $service->bustAssetVersion($aside300);
            $tailScripts = $service->bustAssetVersion($tailScripts);

            // ==== SEO ====
            $metaSeo     = $service->extractMetaSeoAndRewrite($html, config('app.domain'));
            [$main, $metaSeo] = $service->replacePhrasesEverywhere('', $metaSeo);


            // =========

            // Lấy bài theo slug (không cần genreSlug nữa)
            $article = Article::with(['genre:id,slug,name', 'tags:id,name,slug'])
                ->where('slug', $slug)
                ->firstOrFail();

            // Lấy genre chính để dùng trong view
            $genre = $article->genre;

            // tăng view (best-effort)
            try {
                $article->increment('views');
            } catch (\Throwable $e) {
            }

            // Related: ưu tiên theo tag; nếu không có thì cùng chuyên mục
            $tagIds = $article->tags->pluck('id');
            $q = Article::with('genre:id,slug,name')
                ->published()
                ->where('id', '<>', $article->id);

            if ($tagIds->isNotEmpty()) {
                $q->whereHas('tags', fn($qq) => $qq->whereIn('tags.id', $tagIds));
            } elseif ($genre) {
                $q->inAnyGenre($genre->id);
            }

            $related = $q->orderByDesc('published_at')->orderByDesc('id')->take(6)->get();

            // Meta
            $title = $article->meta_title ?: $article->title;
            $desc  = $article->meta_description ?: Str::limit(strip_tags($article->excerpt ?: $article->content), 160);
            $img   = asset_media($article->avatar ?: ($article->thumbnail ?? ''));

            return view('site.article', [
                'aside160'    => $aside160,
                'aside300'    => $aside300,
                'headStyles'  => $headStyles,
                'nav'         => $nav,
                'tailScripts' => $tailScripts,
                'metaSeo'     => $metaSeo,
                'navMobile'   => $navMobile,
                'breadcrumb'  => $breadcrumb,

                'genre'      => $genre,
                'article'    => $article,
                'related'    => $related,
                'meta'       => compact('title', 'desc', 'img'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Mirror xoso failed', ['err' => $e->getMessage(), 'url' => $target]);

            return view('site.article', [
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
