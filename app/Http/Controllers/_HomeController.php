<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Masterminds\HTML5;

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
        $path   = $request->getRequestUri();
        $target = rtrim($this->upstream, '/') . $path;

        try {
            $client = HttpClient::create([
                'verify_peer' => false,
                'verify_host' => false,
                'timeout'     => 20,
                'headers'     => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                    'Accept-Language' => 'vi,en-US;q=0.9',
                    'Referer'         => $this->upstream . '/',
                ],
            ]);

            $res  = $client->request('GET', $target);
            $html = $res->getContent(false);

            // 1) Lấy <style> trong <head> và chuẩn hoá URL thành tuyệt đối theo upstream
            $headStyles = $this->extractHeadStyles($html);

            // 2) Lấy <main class="main">...</main>
            $main = $this->extractMain($html);

            // 3) Lấy <nav class="nav_header">
            $nav  = $this->extractNavHeader($html);

            // 4) Lấy toàn bộ <script> sau js/jsall.min.js (đuôi trang)
            $tailScripts = $this->extractScriptsAfterJsAll($html);

            // Chuẩn hoá lazy attrs -> src/srcset tuyệt đối theo upstream
            $main = $this->fixLazyAttrs($main);

            // Rewrite link & asset nội bộ upstream
            $main = $this->rewriteLinksXoso($main);

            // Xoá <script> trong fragment để tránh xung đột (sẽ render script đuôi riêng)
            $main = $this->stripScripts($main);
            $nav  = $this->stripScripts($nav);

            // Tuỳ chọn: xoá nội dung trong .ads (giữ thẻ rỗng)
            if (function_exists('clearBySelectors')) {
                $main = clearBySelectors($main, '.ads', false);
                $nav  = clearBySelectors($nav,  '.ads', false);
            }

            // ====== Rewrite asset host -> domain hiện tại (proxy/local domain) ======
            $base = rtrim(url('/'), '/');

            // HTML (img, source, iframe, link)
            $main       = rewriteHtmlAssetsToLocalDomain($main, $this->assetHosts, $base);
            $nav        = rewriteHtmlAssetsToLocalDomain($nav,  $this->assetHosts, $base);

            // CSS trong <style>
            $headStyles = rewriteCssUrlsToLocalDomain($headStyles, $this->assetHosts, $base);

            // Script đuôi: chỉ cần rewrite các src của <script>
            $tailScripts = $this->rewriteScriptSrcsToLocalDomain($tailScripts, $this->assetHosts, $base);

            $main = rewriteInlineAjaxCalls($main, '/ajax');

            $main        = $this->bustAssetVersion($main);              // js/css trong main
            $tailScripts = $this->bustAssetVersion($tailScripts);       // js/css ở cuối trang

            $metaSeo = $this->extractMetaSeoAndRewrite($html, config('app.domain'));

            [$main, $metaSeo] = $this->replacePhrasesEverywhere($main, $metaSeo);

            return view('site.index', [
                'main'        => $main,
                'headStyles'  => $headStyles,
                'nav'         => $nav,
                'tailScripts' => $tailScripts,
                'metaSeo'     => $metaSeo
            ]);
        } catch (\Throwable $e) {
            Log::error('Mirror xoso failed', ['err' => $e->getMessage(), 'url' => $target]);

            return view('site.index', [
                'main'        => '<div class="pad">Không tải được nội dung. Vui lòng thử lại sau.</div>',
                'headStyles'  => '',
                'nav'         => '',
                'tailScripts' => '',
            ]);
        }
    }

    /**
     * Lấy outerHTML của tất cả <style> trong <head>, đồng thời chuẩn hoá URL trong CSS về tuyệt đối (upstream).
     */
    protected function extractHeadStyles(string $html): string
    {
        try {
            $crawler = new Crawler($html);
            $styles  = $crawler->filter('head style');
            if ($styles->count() === 0) return '';

            $out = '';
            foreach ($styles as $styleNode) {
                $css = $styleNode->nodeValue ?? '';

                // chuẩn hoá url(...) / @import ... về tuyệt đối trỏ upstream
                $css = $this->absolutizeCssUrls($css);

                $attrs = '';
                if ($styleNode->attributes) {
                    foreach ($styleNode->attributes as $attr) {
                        $attrs .= ' ' . $attr->name . '="' . htmlspecialchars($attr->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    }
                }
                $out .= "<style{$attrs}>\n{$css}\n</style>\n";
            }
            return $out;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Lấy outerHTML của <nav class="nav_header"> và chuẩn hoá nội dung.
     */
    protected function extractNavHeader(string $html): string
    {
        try {
            $crawler = new Crawler($html);
            $node = $crawler->filter('nav.nav_header')->first();
            if ($node->count() === 0) return '';

            $domNode = $node->getNode(0);
            $out = $domNode ? $domNode->ownerDocument->saveHTML($domNode) : '';

            $out = $this->fixLazyAttrs($out);
            $out = $this->rewriteLinksXoso($out);
            $out = $this->stripScripts($out);

            return $out;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Lấy outerHTML của <main class="main">...</main> y nguyên bytes từ nguồn,
     * không dùng DOM parser để tránh libxml "sửa" tag-soup.
     */
    protected function extractMain(string $html): string
    {
        if ($html === '') return '';

        try {
            // 1) Tìm tất cả thẻ mở <main ...> và chọn thẻ có class chứa "main"
            $offset = 0;
            while (preg_match('#<main\b([^>]*)>#i', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $attrsStr = $m[1][0] ?? '';
                $openTag  = $m[0][0];
                $startPos = $m[0][1];

                // Tìm class attribute (hỗ trợ cả "..." '...' và unquoted)
                $class = '';
                if (preg_match('#\bclass\s*=\s*(["\'])(.*?)\1#i', $attrsStr, $c)) {
                    $class = $c[2];
                } elseif (preg_match('#\bclass\s*=\s*([^\s>]+)#i', $attrsStr, $c)) {
                    $class = $c[1];
                }

                // Phải có từ "main" như 1 word trong class
                if ($class !== '' && preg_match('#(?:^|\s)main(?:\s|$)#i', $class)) {
                    // 2) Đếm depth để tìm </main> khớp (hỗ trợ hiếm trường hợp main lồng nhau)
                    $pos   = $startPos + strlen($openTag);
                    $len   = strlen($html);
                    $depth = 1;

                    while ($pos < $len) {
                        if (!preg_match('#<\s*(/?)\s*main\b[^>]*>#i', $html, $mm, PREG_OFFSET_CAPTURE, $pos)) {
                            // Không thấy thẻ đóng -> trả từ mở tới hết
                            return substr($html, $startPos);
                        }

                        $tagStr = $mm[0][0];
                        $tagPos = $mm[0][1];
                        $isClose = ($mm[1][0] === '/');

                        $pos = $tagPos + strlen($tagStr);

                        if ($isClose) {
                            $depth--;
                            if ($depth === 0) {
                                // OuterHTML: từ thẻ mở đến sau thẻ đóng
                                return substr($html, $startPos, $pos - $startPos);
                            }
                        } else {
                            // <main ...> tự đóng "/>" hiếm khi xảy ra — nếu có thì không tăng depth
                            $selfClosing = (substr(trim($tagStr), -2) === '/>');
                            if (!$selfClosing) $depth++;
                        }
                    }
                    // Fallback: không tìm được đóng
                    return substr($html, $startPos);
                }

                // Không phải main.main → tiếp tục tìm thẻ <main> kế tiếp
                $offset = $startPos + strlen($openTag);
            }

            return '';
        } catch (\Throwable $e) {
            return '';
        }
    }


    /**
     * LẤY TẤT CẢ <script> đứng SAU script js/jsall.min.js trong tài liệu gốc.
     * Trả về chuỗi HTML (các thẻ <script> nối nhau).
     */
    protected function extractScriptsAfterJsAll(string $html): string
    {
        if ($html === '') return '';

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $xpath = new \DOMXPath($dom);

        // Tìm script jsall.min.js (ưu tiên theo thứ tự xuất hiện đầu tiên)
        $ref = $xpath->query("(//script[contains(@src, '/js/jsall.min.js') or contains(@src, 'jsall.min.js')])[1]");
        if (!$ref || $ref->length === 0) {
            libxml_clear_errors();
            return '';
        }

        // Lấy tất cả script đứng sau nó (trong document order)
        $nodes = $xpath->query("(//script[contains(@src, '/js/jsall.min.js') or contains(@src, 'jsall.min.js')])[1]/following::script");
        if (!$nodes || $nodes->length === 0) {
            libxml_clear_errors();
            return '';
        }

        $out = '';
        foreach ($nodes as $n) {
            $out .= $dom->saveHTML($n) . "\n";
        }

        libxml_clear_errors();
        return $out;
    }

    /**
     * Rewrite script src trong HTML <script> về domain hiện tại (giữ path/query) với host whitelist
     * VÀ đồng thời:
     *  - LOẠI BỎ các script không mong muốn (Firebase/Google Identity/Login…).
     *  - REWRITE các URL AJAX tương đối ('/path') bên trong inline script thành tuyệt đối theo $this->upstream.
     */
    protected function rewriteScriptSrcsToLocalDomain(string $html, array $hostWhitelist, string $localBase): string
    {
        if ($html === '') return $html;

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        // Bọc fragment để có 1 root ổn định
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="__wrap__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $xpath = new \DOMXPath($dom);
        $root  = $dom->getElementById('__wrap__');

        if (!$root) {
            libxml_clear_errors();
            return $html;
        }

        // ===== Block-list theo SRC (external script)
        $blockSrcPatterns = [
            // chặn calendar và loader có thể nạp calendar
            '#(^|/)(?:js/)?calendar[^/]*\.js(\?|#|$)#i',
            '#(^|/)lottery[_\-]?live[_\-]?all[^/]*\.js(\?|#|$)#i',

            '#firebase\-app\.js#i',
            '#firebase\-messaging\.js#i',
            '#(^|/)gsi/client(\.js)?(\?|#|$)#i', // accounts.google.com/gsi/client
            '#(^|/)loginslim\.min\.js(\?|#|$)#i',
        ];

        // ===== Dấu hiệu nên chặn trong INLINE script
        $blockInlineNeedles = [
            'initializeApp(',
            'getMessaging(',
            'Notification.requestPermission',
            'GoogleClientId',
            'loginslim.min.js',
            'calendar.min.js',
            'calendar.js',
            'loadScript(',
            'import(',
            'require([',
        ];

        // Cần prefix này cho các URL AJAX tương đối trong inline script
        $ajaxBase = ''; // vd: https://xoso.com.vn

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $xpath->query('.//script', $root);
        if ($nodes && $nodes->length) {
            $list = iterator_to_array($nodes);

            /** @var \DOMElement $script */
            foreach ($list as $script) {
                $remove = false;

                // 1) External script có src => chặn theo pattern; nếu không bị chặn thì rewrite sang local domain
                if ($script->hasAttribute('src')) {
                    $src = $script->getAttribute('src');

                    foreach ($blockSrcPatterns as $re) {
                        if (@preg_match($re, $src) && preg_match($re, $src)) {
                            $remove = true;
                            break;
                        }
                    }

                    if (!$remove && function_exists('maybe_rewrite_to_local_domain')) {
                        $rew = maybe_rewrite_to_local_domain($src, $hostWhitelist, $localBase);
                        if ($rew !== $src) {
                            $script->setAttribute('src', $rew);
                        }
                    }
                }

                // 2) Inline / type="module": trước hết xét block-list theo nội dung
                if (!$remove && !$script->hasAttribute('src')) {
                    $code = $script->textContent ?? '';
                    if ($code !== '') {
                        foreach ($blockInlineNeedles as $needle) {
                            if (stripos($code, $needle) !== false) {
                                $remove = true;
                                break;
                            }
                        }
                    }
                }
                if (!$remove && $script->hasAttribute('type') && strtolower($script->getAttribute('type')) === 'module') {
                    $code = $script->textContent ?? '';
                    if ($code !== '' && (stripos($code, 'firebase-app.js') !== false || stripos($code, 'firebase-messaging.js') !== false)) {
                        $remove = true;
                    }
                }

                // 3) Nếu KHÔNG remove, ta rewrite các URL AJAX tương đối bên trong inline script thành tuyệt đối
                if (!$remove && !$script->hasAttribute('src')) {
                    $code = $script->textContent ?? '';
                    if ($code !== '') {
                        // - $.ajax({ url: '/path', ... })
                        $code = preg_replace(
                            '#(\burl\s*:\s*)([\'"])(\/(?!\/)[^\'"]+)\2#',
                            '$1$2' . addcslashes($ajaxBase, '\\$') . '$3$2',
                            $code
                        );

                        // - $.get('/path'  ...), $.post('/path' ...), $.ajax('/path' ...)
                        $code = preg_replace(
                            '#\b\$(?:\.|)(ajax|get|post)\s*\(\s*([\'"])(\/(?!\/)[^\'"]+)\2#i',
                            '$0' /* keep method */, // we will rebuild below using callback to inject base
                            $code
                        );
                        // dùng callback để tiêm base chính xác
                        $code = preg_replace_callback(
                            '#\b\$(?:\.|)(ajax|get|post)\s*\(\s*([\'"])(\/(?!\/)[^\'"]+)\2#i',
                            function ($m) use ($ajaxBase) {
                                $method = $m[1];
                                $q = $m[2];
                                $path = $m[3];
                                return '$' . $method . '(' . $q . $ajaxBase . $path . $q;
                            },
                            $code
                        );

                        // - fetch('/path' ...)
                        $code = preg_replace_callback(
                            '#\bfetch\s*\(\s*([\'"])(\/(?!\/)[^\'"]+)\1#i',
                            function ($m) use ($ajaxBase) {
                                return 'fetch(' . $m[1] . $ajaxBase . $m[2] . $m[1];
                            },
                            $code
                        );

                        // - axios.get('/path' ...), axios.post('/path' ...)
                        $code = preg_replace_callback(
                            '#\baxios\.(get|post|put|delete|patch)\s*\(\s*([\'"])(\/(?!\/)[^\'"]+)\2#i',
                            function ($m) use ($ajaxBase) {
                                return 'axios.' . $m[1] . '(' . $m[2] . $ajaxBase . $m[3] . $m[2];
                            },
                            $code
                        );

                        // - new URL('/path', ...)
                        $code = preg_replace_callback(
                            '#\bnew\s+URL\s*\(\s*([\'"])(\/(?!\/)[^\'"]+)\1#i',
                            function ($m) use ($ajaxBase) {
                                return 'new URL(' . $m[1] . $ajaxBase . $m[2] . $m[1];
                            },
                            $code
                        );

                        // Gán lại nội dung script
                        // (textContent là đủ; DOMDocument sẽ tự escape phù hợp)
                        $script->textContent = $code;
                    }
                }

                if ($remove) {
                    $script->parentNode?->removeChild($script);
                }
            }
        }

        // Xuất lại fragment từ wrapper
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        libxml_clear_errors();
        return $out !== '' ? $out : $html;
    }


    /**
     * Chuẩn hoá lazy-load -> src/srcset tuyệt đối theo upstream.
     */
    protected function fixLazyAttrs(string $html): string
    {
        if ($html === '') return $html;

        $html = preg_replace_callback(
            '#<(img|source|iframe)\b([^>]*)(data-src|data-original)=("|\')([^"\']+)("|\')([^>]*)>#i',
            function ($m) {
                $tag = $m[0];
                $before = $m[2];
                $attr = $m[3];
                $q1 = $m[4];
                $url = $this->absXoso($m[5]);
                $q2 = $m[6];
                $after = $m[7];

                if (stripos($m[1], 'img') === 0 || stripos($m[1], 'iframe') === 0) {
                    if (!preg_match('#\s(src)=("|\')#i', $tag)) {
                        $tag = '<' . $m[1] . $before . ' src="' . $url . '" ' . $attr . '=' . $q1 . $m[5] . $q2 . $after . '>';
                    } else {
                        $tag = preg_replace('#\s(src)=("|\')[^"\']*("|\')#i', ' src="' . $url . '"', $tag, 1);
                    }
                }
                if (stripos($m[1], 'source') === 0) {
                    if (!preg_match('#\s(srcset)=("|\')#i', $tag)) {
                        $tag = preg_replace(
                            '#' . preg_quote($attr . '=' . $q1 . $m[5] . $q2, '#') . '#',
                            'srcset="' . $url . '"',
                            $tag,
                            1
                        );
                    }
                }
                return $tag;
            },
            $html
        );

        $html = preg_replace_callback(
            '#\sdata-srcset=("|\')([^"\']+)("|\')#i',
            function ($m) {
                $list = array_map('trim', explode(',', $m[2]));
                $fixed = [];
                foreach ($list as $item) {
                    $parts = preg_split('/\s+/', trim($item));
                    if (count($parts) > 0) {
                        $url = $this->absXoso($parts[0]);
                        $desc = implode(' ', array_slice($parts, 1));
                        $fixed[] = $desc ? ($url . ' ' . $desc) : $url;
                    }
                }
                return ' srcset="' . implode(', ', $fixed) . '"';
            },
            $html
        );

        return $html;
    }

    /**
     * Rewrite link & asset nội bộ xoso.com.vn
     */
    protected function rewriteLinksXoso(string $html): string
    {
        if ($html === '') return $html;

        // a[href] nội bộ -> path tương đối
        $html = preg_replace(
            '#<a\b([^>]*?)href=("|\')(https?:)?//(?:www\.)?xoso\.com\.vn(/[^"\']*)("|\')#i',
            '<a$1href="$4"$5',
            $html
        );
        $html = preg_replace(
            '#<a\b([^>]*?)href=("|\')//(?:www\.)?xoso\.com\.vn(/[^"\']*)("|\')#i',
            '<a$1href="$3"$4',
            $html
        );

        // protocol-relative -> https
        $html = preg_replace(
            '#\b(src|href)=("|\')//([^"\']+)("|\')#i',
            '$1="https://$3"$2',
            $html
        );

        // asset bắt đầu "/" -> tuyệt đối upstream
        $html = preg_replace_callback(
            '#<(img|script|link)\b[^>]*(src|href)=("|\')(/[^"\']*)("|\')[^>]*>#i',
            function ($m) {
                $tag  = $m[0];
                $attr = $m[2];
                $q    = $m[3];
                $path = $m[4];
                $abs  = $this->upstream . $path;
                return preg_replace(
                    '#' . preg_quote($attr . '=' . $q . $path . $q, '#') . '#',
                    $attr . '=' . $q . $abs . $q,
                    $tag,
                    1
                );
            },
            $html
        );

        // srcset -> tuyệt đối
        $html = preg_replace_callback(
            '#\s(srcset)=("|\')([^"\']+)("|\')#i',
            function ($m) {
                $list = array_map('trim', explode(',', $m[3]));
                $fixed = [];
                foreach ($list as $item) {
                    $parts = preg_split('/\s+/', trim($item));
                    if (count($parts) > 0) {
                        $url = $parts[0];
                        if (str_starts_with($url, '/'))       $url = $this->upstream . $url;
                        elseif (str_starts_with($url, '//'))  $url = 'https:' . $url;
                        $desc = implode(' ', array_slice($parts, 1));
                        $fixed[] = $desc ? ($url . ' ' . $desc) : $url;
                    }
                }
                return ' srcset="' . implode(', ', $fixed) . '"';
            },
            $html
        );

        return $html;
    }

    /**
     * Convert tương đối -> tuyệt đối theo upstream.
     */
    protected function absXoso(string $url): string
    {
        if ($url === '') return $url;
        if (preg_match('#^https?://#i', $url)) return $url;
        if (str_starts_with($url, '//')) return 'https:' . $url;
        if (str_starts_with($url, '/'))  return rtrim($this->upstream, '/') . $url;
        return rtrim($this->upstream, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Bỏ toàn bộ <script> trong fragment để tránh xung đột.
     */
    protected function stripScripts(string $html): string
    {
        if ($html === '') return $html;
        return preg_replace('#<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>#i', '', $html) ?? $html;
    }

    /**
     * Chuẩn hoá url(...) và @import trong CSS thành tuyệt đối trỏ về upstream
     * (giúp bước rewrite về domain hiện tại sau đó chính xác hơn).
     */
    protected function absolutizeCssUrls(string $css): string
    {
        if ($css === '') return $css;

        // url(...)
        $css = preg_replace_callback(
            '#url\(\s*(["\']?)([^)\'"]+)\1\s*\)#i',
            function ($m) {
                $raw = trim($m[2]);
                if (preg_match('#^(data:|https?://|//)#i', $raw)) {
                    return "url({$m[1]}{$raw}{$m[1]})";
                }
                $abs = $this->absXoso($raw);
                return "url({$m[1]}{$abs}{$m[1]})";
            },
            $css
        );

        // @import "..."
        $css = preg_replace_callback(
            '#@import\s+(["\'])([^"\']+)\1#i',
            function ($m) {
                $raw = trim($m[2]);
                if (preg_match('#^(https?://|//)#i', $raw)) {
                    return "@import \"{$raw}\"";
                }
                $abs = $this->absXoso($raw);
                return "@import \"{$abs}\"";
            },
            $css
        );

        // @import url(...)
        $css = preg_replace_callback(
            '#@import\s+url\(\s*(["\']?)([^)\'"]+)\1\s*\)#i',
            function ($m) {
                $raw = trim($m[2]);
                if (preg_match('#^(https?://|//)#i', $raw)) {
                    return "@import url({$raw})";
                }
                $abs = $this->absXoso($raw);
                return "@import url({$abs})";
            },
            $css
        );

        return $css;
    }

    /**
     * Thay hoặc thêm tham số ?v=<timestamp> cho asset (JS/CSS…)
     * - Chỉ đụng tới <script src> và <link href rel=stylesheet> (mặc định).
     * - Giữ nguyên các query khác, giữ nguyên fragment (#...).
     * - Có thể mở rộng sang IMG bằng $includeImg = true.
     * - Chỉ xử lý URL nội bộ (/, ./, ../, hoặc cùng host upstream/local) để tránh phá CDN bên ngoài.
     */
    protected function bustAssetVersion(string $html, ?int $ts = null, bool $includeImg = false): string
    {
        if ($html === '') return $html;
        $ts = $ts ?? time();

        // host nội bộ cho phép (local & upstream)
        $allowHosts = [
            parse_url(url('/'), PHP_URL_HOST),
            parse_url($this->upstream, PHP_URL_HOST),
            'www.' . parse_url($this->upstream, PHP_URL_HOST),
        ];

        // 1) script[src*=.js]
        $html = preg_replace_callback(
            '#<script\b([^>]*?)\bsrc=("|\')([^"\']+\.js[^"\']*)\2([^>]*)>\s*</script>#i',
            function ($m) use ($ts, $allowHosts) {
                $before = $m[1];
                $q = $m[2];
                $url = $m[3];
                $after = $m[4];
                $new = $this->addOrReplaceV($url, $ts, $allowHosts);
                return "<script{$before} src={$q}{$new}{$q}{$after}></script>";
            },
            $html
        );

        // 2) <link ... rel=stylesheet ... href=*.css>
        $html = preg_replace_callback(
            '#<link\b([^>]*\brel=("|\')stylesheet\2[^>]*)\bhref=("|\')([^"\']+\.css[^"\']*)\3([^>]*)>#i',
            function ($m) use ($ts, $allowHosts) {
                $pre = $m[1];
                $q = $m[3];
                $url = $m[4];
                $post = $m[5];
                $new = $this->addOrReplaceV($url, $ts, $allowHosts);
                return "<link {$pre} href={$q}{$new}{$q}{$post}>";
            },
            $html
        );

        // 3) (tuỳ chọn) <img src=...> và <source srcset=...>
        if ($includeImg) {
            $html = preg_replace_callback(
                '#<img\b([^>]*?)\bsrc=("|\')([^"\']+)\2([^>]*)>#i',
                function ($m) use ($ts, $allowHosts) {
                    $before = $m[1];
                    $q = $m[2];
                    $url = $m[3];
                    $after = $m[4];
                    $new = $this->addOrReplaceV($url, $ts, $allowHosts);
                    return "<img{$before} src={$q}{$new}{$q}{$after}>";
                },
                $html
            );

            // srcset: xử lý từng URL trong danh sách
            $html = preg_replace_callback(
                '#\bsrcset=("|\')([^"\']+)\\1#i',
                function ($m) use ($ts, $allowHosts) {
                    $q = $m[1];
                    $list = $m[2];
                    $parts = array_map('trim', explode(',', $list));
                    foreach ($parts as &$item) {
                        $seg = preg_split('/\s+/', $item, 2);
                        $u = $seg[0];
                        $desc = $seg[1] ?? '';
                        $u = $this->addOrReplaceV($u, $ts, $allowHosts);
                        $item = trim($u . ' ' . $desc);
                    }
                    return 'srcset=' . $q . implode(', ', $parts) . $q;
                },
                $html
            );
        }

        return $html;
    }

    /**
     * Thêm hoặc thay tham số v=timestamp cho URL (giữ các query khác, giữ fragment)
     * Chỉ áp dụng cho URL nội bộ hoặc cùng host allow-list.
     */
    protected function addOrReplaceV(string $url, int $ts, array $allowHosts): string
    {
        // Bỏ qua data:, mailto:, tel:
        if (preg_match('#^(data:|mailto:|tel:)#i', $url)) return $url;

        $orig = $url;
        // Chuẩn hoá: nếu là //host/... → thêm https:
        if (str_starts_with($url, '//')) $url = 'https:' . $url;

        $parsed = @parse_url($url);

        // Nếu có host nhưng không thuộc allowHosts → bỏ qua
        if (!empty($parsed['host']) && !in_array(strtolower($parsed['host']), array_map('strtolower', $allowHosts), true)) {
            return $orig;
        }

        $path  = $parsed['path']  ?? '';
        $query = $parsed['query'] ?? '';
        $frag  = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        // Không có path → bỏ qua
        if ($path === '') return $orig;

        // parse query hiện tại
        $params = [];
        if ($query !== '') parse_str($query, $params);

        // replace/add v
        $params['v'] = $ts;

        // build lại query
        $newQuery = http_build_query($params);
        // giữ nguyên scheme/host nếu có, else trả về đường dẫn tương đối
        $prefix = '';
        if (!empty($parsed['scheme'])) {
            $prefix = $parsed['scheme'] . '://';
            if (!empty($parsed['host'])) {
                $prefix .= $parsed['host'];
                if (!empty($parsed['port'])) $prefix .= ':' . $parsed['port'];
            }
        } elseif (!empty($parsed['host'])) {
            // trường hợp đã thêm https: bên trên nên scheme có rồi
            // hoặc URL là //host/... (đã được thêm https)
        }

        // reconstruct
        $rebuilt = ($prefix ? $prefix : '') . $path . ($newQuery ? '?' . $newQuery : '') . $frag;
        // Nếu ban đầu là đường dẫn tương đối (không scheme/host), giữ tương đối:
        if (empty($parsed['scheme']) && empty($parsed['host'])) {
            // xử lý ./ ../ giữ nguyên như cũ
            if (str_starts_with($orig, './')) return './' . ltrim($rebuilt, '/');
            if (str_starts_with($orig, '../')) return '../' . ltrim($rebuilt, '/');
            return $rebuilt;
        }
        return $rebuilt;
    }

    /**
     * Đọc SEO meta từ <head> và REWRITE mọi URL thuộc host xoso.com.vn/static/cdn.xoso.com.vn về $newBase (vd: https://xoso888.win).
     * Trả về mảng $metaSeo sẵn dùng cho Blade.
     */
    /**
     * Đọc SEO meta từ <head> và REWRITE mọi URL thuộc host xoso.com.vn/static/cdn.xoso.com.vn về $newBase (vd: https://xoso888.win),
     * đồng thời thay text "xoso.com.vn" → domain mới trong tất cả nội dung text/meta.
     */
    protected function extractMetaSeoAndRewrite(string $html, string $newBase): array
    {
        $allowHosts = array_map('strtolower', [
            parse_url($this->upstream, PHP_URL_HOST) ?: 'xoso.com.vn',
            'www.' . (parse_url($this->upstream, PHP_URL_HOST) ?: 'xoso.com.vn'),
            'static.xoso.com.vn',
            'cdn.xoso.com.vn',
            'xoso.com.vn',
            'www.xoso.com.vn',
        ]);

        $oldHost = 'xoso.com.vn';
        $newHost = parse_url($newBase, PHP_URL_HOST) ?: 'xoso24h.vip';

        // --- helper: đổi host giữ nguyên path/query/fragment
        $rewriteUrl = function (?string $url) use ($allowHosts, $newBase): ?string {
            if (!$url) return $url;
            $trim = trim($url);
            if ($trim === '' || preg_match('#^(data:|mailto:|tel:)#i', $trim)) return $url;

            // protocol-relative //host/path
            if (strpos($trim, '//') === 0) {
                $trim = 'https:' . $trim;
            }

            $p = @parse_url($trim);
            if ($p === false) return $url;

            // Nếu là relative không host → nâng lên tuyệt đối theo newBase
            if (empty($p['host'])) {
                $path = ($p['path'] ?? '/');
                $query = isset($p['query']) ? ('?' . $p['query']) : '';
                $frag  = isset($p['fragment']) ? ('#' . $p['fragment']) : '';
                return rtrim($newBase, '/') . '/' . ltrim($path, '/') . $query . $frag;
            }

            // Nếu có host mà không thuộc allowHosts → giữ nguyên
            $host = strtolower($p['host']);
            if (!in_array($host, $allowHosts, true)) {
                return $url;
            }

            // Build lại sang newBase
            $base = rtrim($newBase, '/');
            $path = $p['path'] ?? '';
            $query = isset($p['query']) ? ('?' . $p['query']) : '';
            $frag  = isset($p['fragment']) ? ('#' . $p['fragment']) : '';
            return $base . ($path ? $path : '/') . $query . $frag;
        };

        // --- helper: duyệt đệ quy & rewrite các trường URL trong JSON-LD
        $rewriteJsonLd = function ($data) use (&$rewriteJsonLd, $rewriteUrl, $oldHost, $newHost) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (is_string($v) && preg_match('#(^|_)(url|logo|image|@id|contentUrl|sameAs)$#i', (string)$k)) {
                        $data[$k] = $rewriteUrl($v);
                    } else {
                        $data[$k] = $rewriteJsonLd($v);
                    }
                }
                return $data;
            } elseif (is_object($data)) {
                foreach ($data as $k => $v) {
                    if (is_string($v) && preg_match('#(^|_)(url|logo|image|@id|contentUrl|sameAs)$#i', (string)$k)) {
                        $data->$k = $rewriteUrl($v);
                    } else {
                        $data->$k = $rewriteJsonLd($v);
                    }
                }
                return $data;
            } elseif (is_string($data)) {
                // Thay luôn xoso.com.vn -> xoso24h.vip trong text JSON
                $data = str_ireplace($oldHost, $newHost, $data);
                return $rewriteUrl($data);
            }
            return $data;
        };

        try {
            $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
        } catch (\Throwable $e) {
            return [
                'title'     => '',
                'desc'      => '',
                'keywords'  => '',
                'canonical' => '',
                'robots'    => '',
                'author'    => '',
                'favicon'   => '',
                'og'        => [],
                'ld_json'   => [],
            ];
        }

        // ===== TITLE
        $title = '';
        try {
            $title = trim($crawler->filter('head > title')->first()->text(''));
        } catch (\Throwable $e) {
        }

        // ===== META name=*
        $meta = [];
        $crawler->filter('head meta[name]')->each(function ($node) use (&$meta) {
            $name = strtolower(trim($node->attr('name') ?? ''));
            $content = (string) ($node->attr('content') ?? '');
            if ($name !== '') $meta[$name] = $content;
        });

        // ===== META property=og:*
        $og = [];
        $crawler->filter('head meta[property]')->each(function ($node) use (&$og) {
            $prop = strtolower(trim($node->attr('property') ?? ''));
            if (strpos($prop, 'og:') === 0) {
                $og[$prop] = (string) ($node->attr('content') ?? '');
            }
        });

        // ===== canonical & favicon
        $canonical = '';
        try {
            $canonical = (string) ($crawler->filter('head link[rel="canonical"]')->first()->attr('href') ?? '');
        } catch (\Throwable $e) {
        }

        $favicon = '';
        try {
            $ico = $crawler->filter('head link[rel="shortcut icon"], head link[rel="icon"]')->first();
            if ($ico->count()) $favicon = (string) ($ico->attr('href') ?? '');
        } catch (\Throwable $e) {
        }

        // ===== Scripts JSON-LD
        $ldJsonBlocks = [];
        $crawler->filter('head script[type="application/ld+json"]')->each(function ($node) use (&$ldJsonBlocks, $rewriteJsonLd, $oldHost, $newHost) {
            $raw = trim($node->text(''));
            if ($raw === '') return;

            $decoded = json_decode($raw);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rew = $rewriteJsonLd($decoded);
                $ldJsonBlocks[] = json_encode($rew, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                // fallback: thay domain thô
                $replaced = str_ireplace($oldHost, $newHost, $raw);
                $ldJsonBlocks[] = $replaced;
            }
        });

        // ===== REWRITE
        $desc       = str_ireplace($oldHost, $newHost, $meta['description'] ?? '');
        $keywords   = str_ireplace($oldHost, $newHost, $meta['keywords'] ?? '');
        $robots     = $meta['robots']      ?? '';
        $author     = str_ireplace($oldHost, $newHost, $meta['author'] ?? '');
        $title      = str_ireplace($oldHost, $newHost, $title);

        $canonical  = $rewriteUrl($canonical);
        $favicon    = $rewriteUrl($favicon);

        foreach (['og:url', 'og:image', 'og:image:secure_url', 'og:video', 'og:logo'] as $k) {
            if (isset($og[$k])) $og[$k] = $rewriteUrl($og[$k]);
        }

        // og:title/desc/site_name cũng cần thay text
        foreach (['og:title', 'og:description', 'og:site_name'] as $k) {
            if (isset($og[$k])) $og[$k] = str_ireplace($oldHost, $newHost, $og[$k]);
        }

        return [
            'title'     => $title,
            'desc'      => $desc,
            'keywords'  => $keywords,
            'canonical' => $canonical,
            'robots'    => $robots,
            'author'    => $author,
            'favicon'   => $favicon,
            'og'        => $og,
            'ld_json'   => $ldJsonBlocks,
        ];
    }


    /**
     * Tự động thay các cụm thương hiệu như "Xổ số 3 miền" → random ["Xổ số 24h", "Xs24h"]
     * trong cả $main (HTML) và $metaSeo (title, desc, keywords, og:title/description, ld_json text...).
     */
    protected function replacePhrasesEverywhere(string $main, array $metaSeo): array
    {
        // ============================
        // Cấu hình cố định tại đây
        // ============================
        $findList = [
            'Xổ số 3 miền',
            'Kết quả xổ số 3 miền',
        ];

        $replacementPool = [
            'Xổ số 24h',
            'Xs24h',
        ];

        // ============================
        // Logic xử lý
        // ============================

        $findList = array_values(array_unique(array_filter(array_map('trim', $findList), fn($s) => $s !== '')));
        $replacementPool = array_values(array_unique(array_filter(array_map('trim', $replacementPool), fn($s) => $s !== '')));
        if (empty($findList) || empty($replacementPool)) {
            return [$main, $metaSeo];
        }

        // chọn 1 replacement ngẫu nhiên cho toàn bộ page
        $replacement = $replacementPool[random_int(0, count($replacementPool) - 1)];

        // regex alternation, UTF-8, case-insensitive
        $alts = array_map(fn($s) => preg_quote($s, '/'), $findList);
        $pattern = '/(' . implode('|', $alts) . ')/iu';

        // === 1) Thay trong $main (HTML)
        $mainNew = preg_replace($pattern, $replacement, $main);

        // === 2) Thay trong $metaSeo các field text
        $metaNew = $metaSeo;
        $textKeys = ['title', 'desc', 'keywords', 'robots', 'author'];
        foreach ($textKeys as $k) {
            if (!empty($metaNew[$k]) && is_string($metaNew[$k])) {
                $metaNew[$k] = preg_replace($pattern, $replacement, $metaNew[$k]);
            }
        }

        // og:* chỉ thay ở field text, không thay URL
        if (isset($metaNew['og']) && is_array($metaNew['og'])) {
            $ogTextKeys = ['og:title', 'og:description', 'og:site_name', 'twitter:title', 'twitter:description'];
            foreach ($ogTextKeys as $k) {
                if (!empty($metaNew['og'][$k]) && is_string($metaNew['og'][$k])) {
                    $metaNew['og'][$k] = preg_replace($pattern, $replacement, $metaNew['og'][$k]);
                }
            }
        }

        // === 3) ld_json: thay trong giá trị text (không chạm URL)
        if (isset($metaNew['ld_json']) && is_array($metaNew['ld_json'])) {
            $metaNew['ld_json'] = array_map(function ($raw) use ($pattern, $replacement) {
                if (!is_string($raw) || trim($raw) === '') return $raw;

                $decoded = json_decode($raw);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // fallback: thay chuỗi thô
                    return preg_replace($pattern, $replacement, $raw);
                }

                $walker = function ($val) use (&$walker, $pattern, $replacement) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            $val[$k] = $walker($v);
                        }
                        return $val;
                    }
                    if (is_object($val)) {
                        foreach ($val as $k => $v) {
                            $val->$k = $walker($v);
                        }
                        return $val;
                    }
                    if (is_string($val)) {
                        // bỏ qua URL
                        if (preg_match('#^(https?:|mailto:|tel:|data:)#i', $val)) {
                            return $val;
                        }
                        return preg_replace($pattern, $replacement, $val);
                    }
                    return $val;
                };

                $newObj = $walker($decoded);
                return json_encode($newObj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }, $metaNew['ld_json']);
        }

        return [$mainNew, $metaNew];
    }
}
