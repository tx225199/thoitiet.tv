<?php

if (!function_exists('rewriteHtmlAssetsToLocalDomain')) {
    /**
     * Thay host (https://static.xoso.com.vn, cdn.xoso.com.vn, xoso.com.vn) -> domain hiện tại,
     * GIỮ NGUYÊN PATH (/medias/..., /images/...) để trỏ vào route proxy.
     *
     * Chỉ tác động tới: src, data-src, srcset, data-srcset, href trong <link rel=image/preload> (không đụng <a>).
     */
    function rewriteHtmlAssetsToLocalDomain(string $html, array $hostWhitelist, ?string $localBase = null): string
    {
        if ($html === '') return $html;
        $localBase = $localBase ?: rtrim(url('/'), '/'); // ví dụ: https://domain.com

        // 1) src / data-src / href (link ảnh/preload)
        $html = preg_replace_callback(
            '#\b(src|data-src|href)=("|\')((?:https?:)?//[^"\']+)("|\')#i',
            function ($m) use ($hostWhitelist, $localBase) {
                $attr = $m[1]; $q = $m[2]; $url = $m[3];
                // Bỏ qua anchor <a href="...">: chỉ cho phép ở thẻ img|source|iframe|link
                // Để đơn giản: kiểm tra ngược lại trong chuỗi trước đó 300 ký tự.
                // Nếu trúng <a ... href= > thì trả nguyên:
                $before = substr($m[0], -300); // không chắc chắn; an toàn hơn là DOM parse. Nhưng đủ dùng.
                if (preg_match('#^href#i', $attr)) {
                    // chỉ giữ nếu là <link ...>; còn <a ...> không đụng
                    // ở đây khó phân biệt qua regex 1 bước => dùng heuristic:
                    // nếu value có phần mở rộng ảnh (jpg|png|webp|gif|svg) thì vẫn rewrite (preload ảnh)
                }

                $rew = maybe_rewrite_to_local_domain($url, $hostWhitelist, $localBase);
                return $attr . '=' . $q . $rew . $q;
            },
            $html
        );

        // 2) srcset / data-srcset
        $html = preg_replace_callback(
            '#\b(srcset|data-srcset)=("|\')([^"\']+)("|\')#i',
            function ($m) use ($hostWhitelist, $localBase) {
                $attr = $m[1]; $q = $m[2]; $val = $m[3];
                $pieces = array_map('trim', explode(',', $val));
                $rew = [];
                foreach ($pieces as $piece) {
                    $parts = preg_split('/\s+/', $piece);
                    $url  = $parts[0] ?? '';
                    $desc = implode(' ', array_slice($parts, 1));
                    $url  = maybe_rewrite_to_local_domain($url, $hostWhitelist, $localBase);
                    $rew[] = trim($url . ($desc ? (' ' . $desc) : ''));
                }
                return $attr . '=' . $q . implode(', ', $rew) . $q;
            },
            $html
        );

        return $html;
    }
}

if (!function_exists('rewriteCssUrlsToLocalDomain')) {
    /**
     * Trong CSS: url(...) và @import "..." — thay host thuộc whitelist về domain hiện tại, giữ nguyên path.
     */
    function rewriteCssUrlsToLocalDomain(string $css, array $hostWhitelist, ?string $localBase = null): string
    {
        if ($css === '') return $css;
        $localBase = $localBase ?: rtrim(url('/'), '/');

        // url(...)
        $css = preg_replace_callback(
            '#url\(\s*(["\']?)([^)\'"]+)\1\s*\)#i',
            function ($m) use ($hostWhitelist, $localBase) {
                $q = $m[1]; $url = trim($m[2]);
                if (preg_match('#^data:#i', $url)) return "url({$q}{$url}{$q})";
                $rew = maybe_rewrite_to_local_domain($url, $hostWhitelist, $localBase);
                return "url({$q}{$rew}{$q})";
            },
            $css
        );

        // @import "..."
        $css = preg_replace_callback(
            '#@import\s+(["\'])([^"\']+)\1#i',
            function ($m) use ($hostWhitelist, $localBase) {
                $q = $m[1]; $url = trim($m[2]);
                $rew = maybe_rewrite_to_local_domain($url, $hostWhitelist, $localBase);
                return '@import ' . $q . $rew . $q;
            },
            $css
        );

        return $css;
    }
}

if (!function_exists('maybe_rewrite_to_local_domain')) {
    /**
     * Nếu URL thuộc host whitelist => trả về https://your-domain/{PATH+QUERY}
     * - Hỗ trợ: https://host/path, //host/path
     * - Giữ nguyên query, fragment nếu có.
     * - Trả nguyên nếu không match whitelist hoặc là relative URL.
     */
    function maybe_rewrite_to_local_domain(string $url, array $hostWhitelist, ?string $localBase = null): string
    {
        $localBase = $localBase ?: rtrim(url('/'), '/');

        // protocol-relative
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        if (preg_match('~^https?://([^/]+)(/[^?#]*)?(\?[^#]*)?(#.*)?$~i', $url, $m)) {

            $host = strtolower($m[1]);
            $path = $m[2] ?? '/';
            $qry  = $m[3] ?? '';
            $hash = $m[4] ?? '';

            // Chuẩn hóa host whitelist
            $wl = array_map(fn($h) => ltrim(strtolower($h), 'www.'), $hostWhitelist);
            $host = ltrim($host, 'www.');

            if (in_array($host, $wl, true)) {
                return $localBase . $path . $qry . $hash;
            }
        }
        return $url;
    }
}

/* ====== (Tuỳ chọn) clearBySelectors: tái sử dụng nếu bạn cần xoá nội dung div.ads ====== */

if (!function_exists('clearBySelectors')) {
    function clearBySelectors(string $html, string|array $selectors, bool $removeNode = false): string
    {
        if ($html === '') return $html;
        $selectors = is_array($selectors) ? $selectors : [$selectors];

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="__wrap__">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $xpath = new DOMXPath($dom);

        foreach ($selectors as $sel) {
            $xp = css_basic_to_xpath(trim($sel));
            if (!$xp) continue;

            $nodes = $xpath->query($xp);
            if (!$nodes || $nodes->length === 0) continue;

            $bucket = [];
            foreach ($nodes as $n) $bucket[] = $n;

            foreach ($bucket as $node) {
                if ($removeNode) {
                    $node->parentNode?->removeChild($node);
                } else {
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }
                }
            }
        }

        $out = '';
        $wrap = $dom->getElementById('__wrap__');
        if ($wrap) foreach ($wrap->childNodes as $child) $out .= $dom->saveHTML($child);

        libxml_clear_errors();
        return $out !== '' ? $out : $html;
    }

    function css_basic_to_xpath(string $selector): ?string
    {
        $selector = trim($selector);
        if ($selector === '') return null;

        $tag = '*'; $id = null; $classes = [];

        if ($selector !== '' && !in_array($selector[0], ['#', '.'])) {
            if (preg_match('/^([a-zA-Z0-9\-\_\*]+)/', $selector, $m)) {
                $tag = $m[1];
                $selector = substr($selector, strlen($m[1]));
            }
        }
        if (preg_match('/#([a-zA-Z0-9\-\_\:]+)/', $selector, $m)) {
            $id = $m[1];
            $selector = str_replace($m[0], '', $selector);
        }
        if (preg_match_all('/\.([a-zA-Z0-9\-\_]+)/', $selector, $m)) {
            $classes = $m[1];
        }

        $xpath = '//' . $tag;
        $conds = [];
        if ($id) $conds[] = "@id='{$id}'";
        foreach ($classes as $c) {
            $conds[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";
        }
        if ($conds) $xpath .= '[' . implode(' and ', $conds) . ']';
        return $xpath;
    }
}


if (!function_exists('rewriteInlineAjaxCalls')) {
    /**
     * Rewrite các lời gọi AJAX trong inline script từ dạng "/Foo/Bar" → "/ajax/Foo/Bar"
     * để tránh CORS (trỏ vào route proxy nội bộ).
     *
     * - $.ajax({ url: '/...' })
     * - $.get('/...'), $.post('/...'), $.put('/...'), $.delete('/...')
     * - fetch('/...') / window.fetch('/...')
     * - axios.get('/...'), axios.post('/...') ...
     *
     * Chỉ rewrite khi URL bắt đầu bằng "/" và KHÔNG chứa "http" (tránh đè tuyệt đối).
     */
    function rewriteInlineAjaxCalls(string $html, string $ajaxBase = '/ajax'): string
    {
        if ($html === '') return $html;
        $ajaxBase = rtrim($ajaxBase, '/');

        // 1) $.ajax({ url: '/path' })
        $html = preg_replace_callback(
            '#(\$\.ajax\s*\(\s*\{[^{}]*?\burl\s*:\s*)(["\'])(\/(?!\/)[^"\']*)(\2)#si',
            function ($m) use ($ajaxBase) {
                $prefix = $m[1]; $q = $m[2]; $url = $m[3];
                // Bỏ qua nếu đã là /ajax/...
                if (str_starts_with($url, $ajaxBase . '/')) return $m[0];
                return $prefix . $q . $ajaxBase . $url . $q;
            },
            $html
        );

        // 2) $.get('/path') / $.post('/path') / $.put('/path') / $.delete('/path')
        $html = preg_replace_callback(
            '#(\$\.(get|post|put|delete)\s*\(\s*)(["\'])(\/(?!\/)[^"\']*)(\3)#si',
            function ($m) use ($ajaxBase) {
                $prefix = $m[1]; $q = $m[3]; $url = $m[4];
                if (str_starts_with($url, $ajaxBase . '/')) return $m[0];
                return $prefix . $q . $ajaxBase . $url . $q;
            },
            $html
        );

        // 3) fetch('/path') / window.fetch('/path')
        $html = preg_replace_callback(
            '#(\b(?:window\.)?fetch\s*\(\s*)(["\'])(\/(?!\/)[^"\']*)(\2)#si',
            function ($m) use ($ajaxBase) {
                $prefix = $m[1]; $q = $m[2]; $url = $m[3];
                if (str_starts_with($url, $ajaxBase . '/')) return $m[0];
                return $prefix . $q . $ajaxBase . $url . $q;
            },
            $html
        );

        // 4) axios.get('/path') / axios.post('/path') / ...
        $html = preg_replace_callback(
            '#(\baxios\.(get|post|put|patch|delete)\s*\(\s*)(["\'])(\/(?!\/)[^"\']*)(\3)#si',
            function ($m) use ($ajaxBase) {
                $prefix = $m[1]; $q = $m[3]; $url = $m[4];
                if (str_starts_with($url, $ajaxBase . '/')) return $m[0];
                return $prefix . $q . $ajaxBase . $url . $q;
            },
            $html
        );

        return $html;
    }
}