<?php

if (!function_exists('clearBySelectors')) {
    /**
     * Xóa NỘI DUNG (innerHTML) của các node khớp selector (hoặc xóa cả node nếu $removeNode = true).
     * Hỗ trợ selector cơ bản kiểu CSS:
     *  - "#id"
     *  - ".class", ".a.b" (nhiều class)
     *  - "div#id"
     *  - "div.class", "div.a.b"
     *
     * @param  string              $html        Fragment HTML cần xử lý
     * @param  string|string[]     $selectors   Selector hoặc mảng selector
     * @param  bool                $removeNode  true: xóa hẳn node; false: chỉ xóa innerHTML
     * @return string
     */
    function clearBySelectors(string $html, string|array $selectors, bool $removeNode = false)
    {
        if ($html === '') return $html;

        $selectors = is_array($selectors) ? $selectors : [$selectors];

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        // Bọc fragment để DOM có 1 root ổn định
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="__wrap__">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);
        $root  = $dom->getElementById('__wrap__');

        if ($root) {
            foreach ($selectors as $sel) {
                $query = _helpers_css_to_xpath($sel);
                if (!$query) continue;

                // Query trong context wrapper
                $nodes = $xpath->query($query, $root);
                if (!$nodes || $nodes->length === 0) continue;

                // clone danh sách để tránh iterator bị ảnh hưởng bởi việc remove
                $list = iterator_to_array($nodes);
                /** @var \DOMElement $node */
                foreach ($list as $node) {
                    if ($removeNode) {
                        $node->parentNode?->removeChild($node);
                    } else {
                        // Xóa toàn bộ children (giữ lại chính node)
                        while ($node->firstChild) {
                            $node->removeChild($node->firstChild);
                        }
                    }
                }
            }
        }

        // Lấy lại HTML từ wrapper
        $out = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
        }

        libxml_clear_errors();
        return $out !== '' ? $out : $html;
    }
}

if (!function_exists('_helpers_css_to_xpath')) {
    /**
     * Chuyển selector CSS cơ bản thành XPath phù hợp để query trong context node.
     * Trả về chuỗi bắt đầu bằng ".//tag[...]"
     *
     * Hỗ trợ:
     *  - "#id"
     *  - ".class", ".a.b"
     *  - "div#id"
     *  - "div.class", "div.a.b"
     */
    function _helpers_css_to_xpath(string $selector): ?string
    {
        $selector = trim($selector);
        if ($selector === '') return null;

        $tag     = '*';
        $id      = null;
        $classes = [];

        // #id
        if ($selector[0] === '#') {
            $id = substr($selector, 1);
        }
        // .class or .a.b
        elseif ($selector[0] === '.') {
            $classes = array_values(array_filter(explode('.', $selector)));
        }
        // tag / tag#id / tag.class(.more)
        else {
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9-]*/', $selector, $m)) {
                $tag = $m[0];
                $rest = substr($selector, strlen($tag));

                if ($rest !== '') {
                    if (preg_match('/#([a-zA-Z_][a-zA-Z0-9_-]*)/', $rest, $m2)) {
                        $id = $m2[1];
                    }
                    if (preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $rest, $m3)) {
                        $classes = $m3[1];
                    }
                }
            } else {
                if (str_contains($selector, '#')) {
                    if (preg_match('/#([a-zA-Z_][a-zA-Z0-9_-]*)/', $selector, $m2)) {
                        $id = $m2[1];
                    }
                }
                if (str_contains($selector, '.')) {
                    if (preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $selector, $m3)) {
                        $classes = $m3[1];
                    }
                }
            }
        }

        $x = './/' . $tag;
        $conds = [];

        if ($id) {
            $conds[] = '@id="'._helpers_xpath_escape_attr($id).'"';
        }
        foreach ($classes as $cls) {
            $cls = _helpers_xpath_escape_space($cls);
            $conds[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$cls} ')";
        }

        if (!empty($conds)) {
            $x .= '[' . implode(' and ', $conds) . ']';
        }

        return $x;
    }
}

if (!function_exists('_helpers_xpath_escape_attr')) {
    /**
     * Escape đơn giản giá trị attr cho XPath so sánh bằng "..."
     * (Thực tế id/class hiếm khi chứa dấu ")
     */
    function _helpers_xpath_escape_attr(string $value): string
    {
        return str_replace('"', '', $value);
    }
}

if (!function_exists('_helpers_xpath_escape_space')) {
    /**
     * Loại bỏ khoảng trắng nguy cơ trong token class cho câu contains(...)
     */
    function _helpers_xpath_escape_space(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? $value;
    }
}
