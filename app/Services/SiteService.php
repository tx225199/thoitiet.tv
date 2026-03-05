<?php

namespace App\Services;

use App\Models\Genre;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class SiteService
{

    public string $upstream = 'https://xoso.com.vn';

    private function extractOuterByTagAndClass(string $html, string $tag, string $classWord): string
    {
        if ($html === '') return '';

        $tagRe  = preg_quote($tag, '#');
        $offset = 0;

        while (preg_match("#<{$tagRe}\\b([^>]*)>#i", $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $attrsStr = $m[1][0] ?? '';
            $openTag  = $m[0][0];
            $startPos = $m[0][1];

            // lấy class attr (", ', unquoted)
            $class = '';
            if (preg_match('#\\bclass\\s*=\\s*(["\'])(.*?)\\1#i', $attrsStr, $c)) {
                $class = $c[2];
            } elseif (preg_match('#\\bclass\\s*=\\s*([^\\s>]+)#i', $attrsStr, $c)) {
                $class = $c[1];
            }

            // phải có classWord như 1 từ riêng trong class
            if ($class !== '' && preg_match('#(?:^|\\s)' . preg_quote($classWord, '#') . '(?:\\s|$)#i', $class)) {
                $innerStart = $startPos + strlen($openTag); // BẮT ĐẦU sau thẻ mở
                $pos        = $innerStart;
                $len        = strlen($html);
                $depth      = 1;

                while ($pos < $len) {
                    if (!preg_match("#<\\s*(/?)\\s*{$tagRe}\\b[^>]*>#i", $html, $mm, PREG_OFFSET_CAPTURE, $pos)) {
                        // không tìm thấy thẻ đóng -> trả phần từ sau open đến hết
                        return substr($html, $innerStart);
                    }

                    $tagStr  = $mm[0][0];
                    $tagPos  = $mm[0][1];
                    $isClose = ($mm[1][0] === '/');
                    $pos     = $tagPos + strlen($tagStr);

                    if ($isClose) {
                        $depth--;
                        if ($depth === 0) {
                            // INNER HTML: từ sau open đến trước close
                            return substr($html, $innerStart, $tagPos - $innerStart);
                        }
                    } else {
                        // tự đóng "/>" thì không tăng depth
                        $selfClosing = (substr(trim($tagStr), -2) === '/>');
                        if (!$selfClosing) $depth++;
                    }
                }

                // fallback nếu không thấy close
                return substr($html, $innerStart);
            }

            // không khớp class -> kiểm tiếp thẻ cùng loại
            $offset = $startPos + strlen($openTag);
        }

        return '';
    }

    /** Lấy outerHTML của <div class="content-left">…</div> */
    public function extractContentLeft(string $html): string
    {
        return $this->extractOuterByTagAndClass($html, 'div', 'content-left');
    }

    /** Lấy outerHTML của <aside class="aside-160">…</aside> */
    public function extractAside160(string $html): string
    {
        return $this->extractOuterByTagAndClass($html, 'aside', 'aside-160');
    }

    /** Lấy outerHTML của <aside class="aside-300">…</aside> */
    public function extractAside300(string $html): string
    {
        return $this->extractOuterByTagAndClass($html, 'aside', 'aside-300');
    }


    /**
     * Lấy outerHTML của tất cả <style> trong <head>, đồng thời chuẩn hoá URL trong CSS về tuyệt đối (upstream).
     */
    public function extractHeadStyles(string $html): string
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
     * Lấy outerHTML của <main class="main">...</main> y nguyên bytes từ nguồn,
     * không dùng DOM parser để tránh libxml "sửa" tag-soup.
     */
    public function _extractMain(string $html): string
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
     * Lấy outerHTML của <nav class="nav_header"> và chuẩn hoá nội dung.
     */
    public function extractNavHeader(string $html): string
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
     * Chuẩn hoá lazy-load -> src/srcset tuyệt đối theo upstream.
     */
    public function fixLazyAttrs(string $html): string
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
    public function rewriteLinksXoso(string $html): string
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
     * Bỏ toàn bộ <script> trong fragment để tránh xung đột.
     */
    public function stripScripts(string $html): string
    {
        if ($html === '') return $html;
        return preg_replace('#<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>#i', '', $html) ?? $html;
    }

    /**
     * Rewrite script src trong HTML <script> về domain hiện tại (giữ path/query) với host whitelist
     * VÀ đồng thời:
     *  - LOẠI BỎ các script không mong muốn (Firebase/Google Identity/Login…).
     *  - REWRITE các URL AJAX tương đối ('/path') bên trong inline script thành tuyệt đối theo $this->upstream.
     */
    public function rewriteScriptSrcsToLocalDomain(string $html, array $hostWhitelist, string $localBase): string
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
     * Thay hoặc thêm tham số ?v=<timestamp> cho asset (JS/CSS…)
     * - Chỉ đụng tới <script src> và <link href rel=stylesheet> (mặc định).
     * - Giữ nguyên các query khác, giữ nguyên fragment (#...).
     * - Có thể mở rộng sang IMG bằng $includeImg = true.
     * - Chỉ xử lý URL nội bộ (/, ./, ../, hoặc cùng host upstream/local) để tránh phá CDN bên ngoài.
     */
    public function bustAssetVersion(string $html, ?int $ts = null, bool $includeImg = false): string
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
     * Đọc SEO meta từ <head> và REWRITE mọi URL thuộc host xoso.com.vn/static/cdn.xoso.com.vn về $newBase (vd: https://xoso888.win),
     * đồng thời thay text "xoso.com.vn" → domain mới trong tất cả nội dung text/meta.
     */
    public function extractMetaSeoAndRewrite(string $html, string $newBase): array
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
    public function replacePhrasesEverywhere(string $main, array $metaSeo): array
    {
        // ============================
        // Cấu hình cố định tại đây
        // ============================
        $findList = [
            'Xổ số 3 miền',
            'Kết quả xổ số 3 miền',
            'xoso.com.vn'
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

    /**
     * LẤY TẤT CẢ <script> đứng SAU script js/jsall.min.js trong tài liệu gốc.
     * Trả về chuỗi HTML (các thẻ <script> nối nhau).
     */
    public function extractScriptsAfterJsAll(string $html): string
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
     * Lấy outerHTML của mobile nav (aside pushbar) và chuẩn hoá nội dung.
     * Ưu tiên: <aside data-pushbar-id="left">; fallback: .pushbar.from_left, .pushbar.from-left, .pushbar.fromLeft, .pushbar
     */
    public function extractNavMobile(string $html): string
    {
        try {
            $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

            // Thử lần lượt các selector phổ biến để bền trước thay đổi DOM nhỏ
            $selectors = [
                'aside[data-pushbar-id="left"]',
                'aside.pushbar.from_left',
                'aside.pushbar.from-left',
                'aside.pushbar.fromLeft',
                'aside.pushbar', // fallback cuối
            ];

            $node = null;
            foreach ($selectors as $sel) {
                $try = $crawler->filter($sel);
                if ($try->count() > 0) {
                    $node = $try->first();
                    break;
                }
            }

            if (!$node || $node->count() === 0) {
                return '';
            }

            // Lấy outerHTML
            $domNode = $node->getNode(0);
            $out = $domNode ? $domNode->ownerDocument->saveHTML($domNode) : '';
            if ($out === '' || $out === null) {
                return '';
            }

            // Chuẩn hoá nội dung giống nav_header
            $out = $this->fixLazyAttrs($out);       // data-src -> src tuyệt đối theo upstream
            $out = $this->rewriteLinksXoso($out);   // rewrite internal href theo quy ước của bạn
            $out = $this->stripScripts($out);       // loại <script> để tránh xung đột

            // (tuỳ chọn) nếu bạn có helper clearBySelectors:
            if (function_exists('clearBySelectors')) {
                $out = clearBySelectors($out, '.ads', false);
            }

            return $out;
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function injectGenresIntoNav(?string $navHtml): ?string
    {
        if (!$navHtml || trim($navHtml) === '') return $navHtml;

        // 1) Genres
        $genres = Genre::query()
            ->where('hidden', 0)
            ->select(['id', 'slug', 'name'])
            ->orderByRaw("CASE WHEN slug = 'tin-xo-so' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        if ($genres->isEmpty()) return $navHtml;

        $parentSlug = 'tin-xo-so';
        $parent     = $genres->firstWhere('slug', $parentSlug);
        $parentName = $parent ? $parent->name : 'Tin Xổ Số';

        // 2) Build URL qua route name `genre`
        $genreUrl = function (string $slug): string {
            try {
                // absolute = false → trả URL tương đối (/genre/slug)
                return route('genre', ['slug' => $slug], false);
            } catch (\Throwable $e) {
                // fallback an toàn nếu route chưa load ở context nào đó
                return url('/genre/' . ltrim($slug, '/'));
            }
        };

        // 3) Active: /genre/{slug}
        $isActive = function (string $slug): bool {
            $uri = trim(request()->path() ?? '', '/'); // ví dụ: "genre/tin-xo-so"
            return $uri === ('genre/' . trim($slug, '/'));
        };

        // 4) Parse DOM
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
                . '<div id="__wrap__">' . $navHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // ul.menu_ul đầu tiên
        $ul = $xpath->query("//nav[contains(concat(' ', normalize-space(@class), ' '), ' nav_header ')]//ul[contains(concat(' ', normalize-space(@class), ' '), ' menu_ul ')][1]")->item(0)
            ?? $xpath->query("//ul[contains(concat(' ', normalize-space(@class), ' '), ' menu_ul ')][1]")->item(0);
        if (!$ul instanceof \DOMElement) return $navHtml;

        // 5) Xoá <li.menu_li> CUỐI
        $lis = $xpath->query("./li[contains(concat(' ', normalize-space(@class), ' '), ' menu_li ')]", $ul);
        if ($lis && $lis->length > 0) {
            $lastLi = $lis->item($lis->length - 1);
            if ($lastLi && $lastLi->parentNode) {
                $lastLi->parentNode->removeChild($lastLi);
            }
        }

        // 6) Tạo LI mới “Tin Xổ Số” + submenu = tất cả genres còn lại
        $liNew = $dom->createElement('li');
        $liNew->setAttribute('class', 'menu_li');

        $a = $dom->createElement('a', $parentName); // createElement sẽ tự escape text
        $a->setAttribute('href', $genreUrl($parentSlug));
        $a->setAttribute('title', $parentName);
        $a->setAttribute('class', $isActive($parentSlug) ? 'menu_a active' : 'menu_a');
        $liNew->appendChild($a);

        $ulDown = $dom->createElement('ul');
        $ulDown->setAttribute('class', 'menu_down');

        foreach ($genres as $g) {
            if ($g->slug === $parentSlug) continue;

            $liChild = $dom->createElement('li');
            $aChild  = $dom->createElement('a', $g->name);
            $aChild->setAttribute('href', $genreUrl($g->slug));
            $aChild->setAttribute('title', $g->name);
            $liChild->appendChild($aChild);
            $ulDown->appendChild($liChild);
        }

        $liNew->appendChild($ulDown);

        // 7) Append lại CUỐI ul
        $ul->appendChild($liNew);

        // 8) Xuất lại fragment
        $wrap = $xpath->query("//div[@id='__wrap__']")->item(0);
        if (!$wrap) return $navHtml;

        $result = '';
        foreach ($wrap->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        return $result ?: $navHtml;
    }

    public function injectMobileGenresIntoNav(?string $mobileHtml): ?string
    {
        if (!$mobileHtml || trim($mobileHtml) === '') return $mobileHtml;

        // 1) Lấy genres
        $genres = Genre::query()
            ->where('hidden', 0)
            ->select(['id', 'slug', 'name'])
            ->orderByRaw("CASE WHEN slug = 'tin-xo-so' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        if ($genres->isEmpty()) return $mobileHtml;

        $parentSlug = 'tin-xo-so';
        $parent     = $genres->firstWhere('slug', $parentSlug);
        $parentName = $parent ? $parent->name : 'Tin Xổ Số';

        // 2) URL
        $genreUrl = function (string $slug): string {
            try {
                return route('genre', ['slug' => $slug], false);
            } catch (\Throwable $e) {
                return url('/genre/' . ltrim($slug, '/'));
            }
        };

        // 3) Active checker
        $isActive = function (string $slug): bool {
            $uri = trim(request()->path() ?? '', '/');
            return $uri === ('genre/' . trim($slug, '/'));
        };

        // 4) Parse DOM
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
                . '<div id="__wrap_mobile__">' . $mobileHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xp = new \DOMXPath($dom);

        // 5) Tìm đúng .ac_item Tin Xổ Số
        /** @var \DOMElement|null $targetA */
        $targetA = $xp->query(
            "//div[@id='__wrap_mobile__']//div[contains(@class,'ac_item')]//a[contains(@class,'ac_title_2')][contains(normalize-space(.), 'Tin Xổ số')]"
        )->item(0);

        if (!$targetA instanceof \DOMElement) {
            $targetA = $xp->query(
                "//div[@id='__wrap_mobile__']//div[contains(@class,'ac_item')]//a[contains(@class,'ac_title_2')][contains(translate(@href,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'tin-xo-so')]"
            )->item(0);
        }
        if (!$targetA instanceof \DOMElement) return $mobileHtml;

        /** @var \DOMElement|null $item */
        $item = $xp->query('ancestor::div[contains(@class,"ac_item")][1]', $targetA)->item(0);
        if (!$item instanceof \DOMElement) return $mobileHtml;

        // 6) Cập nhật anchor cha
        $parentActive = $isActive($parentSlug);
        $targetA->setAttribute('href', $genreUrl($parentSlug));
        $targetA->setAttribute(
            'class',
            trim('ac_title_2' . ($parentActive ? ' active' : ''))
        );
        $targetA->setAttribute('title', $parentName);

        // Xoá text node cũ, giữ icon <img>
        foreach (iterator_to_array($targetA->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $targetA->removeChild($child);
            }
        }
        $targetA->appendChild($dom->createTextNode($parentName . ' '));

        // 7) Tìm hoặc tạo div.ac_content
        /** @var \DOMElement|null $acContent */
        $acContent = $xp->query('.//div[contains(@class,"ac_content")]', $item)->item(0);
        if (!$acContent instanceof \DOMElement) {
            $acContent = $dom->createElement('div');
            $acContent->setAttribute('class', 'ac_content');
            $item->appendChild($acContent);
        }

        // Xoá nội dung cũ
        while ($acContent->firstChild) {
            $acContent->removeChild($acContent->firstChild);
        }

        // 8) Tạo div.ac_ul2 mới
        $ul2 = $dom->createElement('div');
        $ul2->setAttribute('class', 'ac_ul2');

        $childActiveFound = false;
        foreach ($genres as $g) {
            if ($g->slug === $parentSlug) continue;

            $a = $dom->createElement('a', $g->name);
            $a->setAttribute('href', $genreUrl($g->slug));
            $a->setAttribute('title', $g->name);

            if ($isActive($g->slug)) {
                $a->setAttribute('class', 'active');
                $childActiveFound = true;
            }

            $ul2->appendChild($a);
            $ul2->appendChild($dom->createTextNode(' ')); // giữ format khoảng trắng
        }

        $acContent->appendChild($ul2);

        // 9) Mở/đóng submenu theo trạng thái active
        if ($parentActive || $childActiveFound) {
            $item->setAttribute('class', trim($item->getAttribute('class') . ' ac_open'));
            $acContent->setAttribute('style', 'display: block;');
        } else {
            $cls = preg_replace('/\bac_open\b/', '', $item->getAttribute('class'));
            $item->setAttribute('class', trim($cls));
            $acContent->setAttribute('style', 'display: none;');
        }

        // 10) Xuất lại HTML
        /** @var \DOMElement|null $wrap */
        $wrap = $xp->query("//div[@id='__wrap_mobile__']")->item(0);
        if (!$wrap instanceof \DOMElement) return $mobileHtml;

        $result = '';
        foreach ($wrap->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result ?: $mobileHtml;
    }

    public function extractBreadcrumb(string $html): string
    {
        if ($html === '') return '';

        $offset = 0;
        while (preg_match('#<div\b([^>]*)>#i', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $attrsStr = $m[1][0] ?? '';
            $openTag  = $m[0][0];
            $startPos = $m[0][1];

            // lấy giá trị class (hỗ trợ "..." '...' và unquoted)
            $class = '';
            if (preg_match('#\bclass\s*=\s*(["\'])(.*?)\1#i', $attrsStr, $c)) {
                $class = $c[2];
            } elseif (preg_match('#\bclass\s*=\s*([^\s>]+)#i', $attrsStr, $c)) {
                $class = $c[1];
            }

            // class phải chứa từ "breadcrumb" như 1 word
            if ($class !== '' && preg_match('#(?:^|\s)breadcrumb(?:\s|$)#i', $class)) {
                $pos   = $startPos + strlen($openTag); // sau thẻ mở
                $len   = strlen($html);
                $depth = 1;
                // đếm <div ...> / </div>
                while ($pos < $len) {
                    if (!preg_match('#<\s*(/?)\s*div\b[^>]*>#i', $html, $mm, PREG_OFFSET_CAPTURE, $pos)) {
                        // không thấy đóng → trả từ mở tới hết
                        return substr($html, $startPos);
                    }
                    $tagStr  = $mm[0][0];
                    $tagPos  = $mm[0][1];
                    $isClose = ($mm[1][0] === '/');
                    $pos     = $tagPos + strlen($tagStr);

                    if ($isClose) {
                        $depth--;
                        if ($depth === 0) {
                            return substr($html, $startPos, $pos - $startPos);
                        }
                    } else {
                        $selfClosing = (substr(trim($tagStr), -2) === '/>');
                        if (!$selfClosing) $depth++;
                    }
                }
                return substr($html, $startPos);
            }

            $offset = $startPos + strlen($openTag);
        }

        return '';
    }

    public function replaceMobileSidebarLogo(string $mobileHtml, array $arrSettings): string
    {
        if (trim($mobileHtml) === '') return $mobileHtml;

        // Lấy URL logo từ settings (fallback về /images/logo.svg)
        $logoUrl = !empty($arrSettings['logo'])
            ? sourceSetting($arrSettings['logo'])
            : url('/images/logo.svg');

        // Parse DOM
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
                . '<div id="__wrap_mobile_logo__">' . $mobileHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xp = new \DOMXPath($dom);

        // Tìm img logo trong user-sidebar
        /** @var \DOMElement|null $img */
        $img = $xp->query("//div[@id='__wrap_mobile_logo__']//div[contains(@class,'user-sidebar')]//img")->item(0);
        if (!$img instanceof \DOMElement) {
            // fallback: tìm theo class .logosidebar
            $img = $xp->query("//div[@id='__wrap_mobile_logo__']//img[contains(concat(' ', normalize-space(@class), ' '), ' logosidebar ')]")->item(0);
        }
        if ($img instanceof \DOMElement) {
            $img->setAttribute('src', $logoUrl);
            $img->setAttribute('data-src', $logoUrl); // nếu có lazy-load
            if (!$img->hasAttribute('alt') || trim((string)$img->getAttribute('alt')) === '') {
                $img->setAttribute('alt', 'Trang chủ');
            }
        }

        // Xuất lại fragment
        /** @var \DOMElement|null $wrap */
        $wrap = $xp->query("//div[@id='__wrap_mobile_logo__']")->item(0);
        if (!$wrap instanceof \DOMElement) return $mobileHtml;

        $result = '';
        foreach ($wrap->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        return $result ?: $mobileHtml;
    }
}
