<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\Genre;
use App\Models\Media;
use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class CrawlArticleJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 90, 180, 300];
    public int $timeout = 90;
    public int $uniqueFor = 1800;

    public function __construct(
        public string  $url,
        public ?string $fallbackTitle = null,
        public ?string $fallbackExcerpt = null,
        public ?string $fallbackThumb = null,
        public bool    $isVideoHint = false,
        public ?int    $cateId = null // <<< THÊM: cateId để map genre
    ) {}

    public function uniqueId(): string
    {
        return md5($this->url);
    }

    public function handle(): void
    {
        /* === BASE & GENRE === */
        $base  = 'https://xoso.com.vn';
        $genre = $this->resolveGenre($this->cateId);

        if (!$genre) {
            Log::warning('Genre not found, skip', ['url' => $this->url, 'cateId' => $this->cateId]);
            return;
        }

        // Idempotent theo URL
        if (Article::where('url', $this->url)->exists()) {
            Log::info('Skip existed article by url', ['url' => $this->url]);
            return;
        }

        // factory tạo client (có/không proxy)
        $makeClient = function (?string $px) use ($base) {
            return new Client(HttpClient::create([
                'proxy'        => $px ?: null,
                'verify_peer'  => false,
                'verify_host'  => false,
                'timeout'      => 25,
                'headers'      => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                    'Accept-Language' => 'vi,en-US;q=0.9',
                    'Referer'         => $base . '/',
                ],
            ]));
        };

        // lấy proxy & lọc hợp lệ
        $rawProxy = function_exists('getRandomProxy') ? getRandomProxy() : null;
        $useProxy = (function (?string $px): ?string {
            if (!$px) return null;
            $parts = @parse_url($px);
            if (!$parts || empty($parts['host']) || empty($parts['scheme'])) return null;
            if (in_array(strtolower($parts['scheme']), ['http', 'https', 'socks5', 'socks5h'], true)) {
                return $px;
            }
            return null;
        })($rawProxy);

        try {
            $client  = $makeClient($useProxy);
            try {
                $crawler = $client->request('GET', $this->url);
                $status  = $client->getResponse()->getStatusCode();
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $isProxyAuth = str_contains($msg, 'response 407') || str_contains($msg, 'Proxy Authentication') || str_contains($msg, 'proxy');
                if ($isProxyAuth && $useProxy) {
                    Log::warning('Proxy 407 → retry without proxy', ['url' => $this->url, 'proxy' => $useProxy]);
                    $client  = $makeClient(null);
                    $crawler = $client->request('GET', $this->url);
                    $status  = $client->getResponse()->getStatusCode();
                } else {
                    throw $e;
                }
            }

            // Phân loại HTTP
            if ($status >= 500) {
                $this->release(120);
                return;
            }
            if ($status === 429) {
                $this->release(300);
                return;
            }
            if (in_array($status, [403, 404], true)) {
                Log::warning('Skip 4xx', ['url' => $this->url, 'status' => $status]);
                return;
            }

            /* === HELPERS NHẸ === */
            $getText = fn(Crawler $n, string $sel, ?string $def = null)
            => $n->filter($sel)->count() ? trim($n->filter($sel)->text()) : $def;

            $getAttr = fn(Crawler $n, string $sel, string $attr, ?string $def = null)
            => $n->filter($sel)->count() ? ($n->filter($sel)->attr($attr) ?? $def) : $def;

            /* === PARSE DOM XOSO === */
            $articleNode = $client ? ($client && $crawler->filter('article.the-article')->count()
                ? $crawler->filter('article.the-article')->first()
                : null) : null;

            // Title
            $title = $articleNode ? $getText($articleNode, '.the-article-header .the-article-title') : null;
            if (!$title) $title = $this->fallbackTitle;
            if (!$title) {
                Log::warning('No title parsed, skip', ['url' => $this->url]);
                return;
            }

            // Time
            $timeText = $articleNode ? $getText($articleNode, '.the-article-header .the-article-time') : null;
            $publishedAt = null;
            if ($timeText && preg_match('/(\d{2}\/\d{2}\/\d{4}).*?(\d{1,2}:\d{2})/u', $timeText, $m)) {
                $publishedAt = \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$m[1]} {$m[2]}", 'Asia/Ho_Chi_Minh');
            }

            // Content
            $contentNode = $articleNode && $articleNode->filter('.the-article-content')->count()
                ? $articleNode->filter('.the-article-content')->first()
                : null;
            $contentHtml = $contentNode ? $this->normalizeContentHtml($contentNode->html()) : null;

            // Danh sách domain nguồn cần thay (tùy bạn bổ sung)
            $sourceDomains = ['xoso.com.vn'];

            // app.url phải được set trong .env để url local chuẩn
            $contentHtml = $this->rewriteAnchorDomains($contentHtml, $sourceDomains, config('app.domain'));


            // Excerpt
            $excerpt = $this->fallbackExcerpt;
            if (!$excerpt && $contentNode && $contentNode->filter('p')->count()) {
                $excerpt = \Illuminate\Support\Str::limit(trim($contentNode->filter('p')->first()->text('')), 220);
            }

            // Meta
            $metaTitle       = $getText($crawler, 'title', $title);
            $metaDescription = $getAttr($crawler, 'meta[name="description"]', 'content', $excerpt);
            $metaKeywords    = $getAttr($crawler, 'meta[name="keywords"]', 'content');

            // Thumbnail
            $thumb = $getAttr($crawler, 'meta[property="og:image"]', 'content')
                ?: ($contentNode ? $this->firstImageFromContent($contentNode) : null)
                ?: $this->fallbackThumb;
            $thumb = $this->toAbsoluteUrl($thumb, $base);

            // Author
            $author = null;
            if ($articleNode && $articleNode->filter('.the-article-author .name-author')->count()) {
                $author = trim($articleNode->filter('.the-article-author .name-author')->text(''));
            }

            // Video (iframe / mp4)
            [$videoList, $foundVideo] = $this->extractVideosWide($crawler, $base);
            $isVideo = $this->isVideoHint || $foundVideo;

            $hasIframeInContent = $contentHtml && preg_match('/<iframe\b[^>]*src=/i', $contentHtml);
            if ($isVideo && !$hasIframeInContent) {
                $embedUrl = null;
                foreach ($videoList as $v) {
                    if ($v['type'] === 'embed') {
                        $embedUrl = $v['url'];
                        break;
                    }
                }
                if ($embedUrl) {
                    $iframe = '<div class="video-embed"><iframe src="' . htmlspecialchars($embedUrl, ENT_QUOTES) . '" frameborder="0" allowfullscreen loading="lazy"></iframe></div>';
                    $contentHtml = $iframe . (string)$contentHtml;
                } elseif (!empty($videoList) && $videoList[0]['type'] === 'mp4') {
                    $vurl = $videoList[0]['url'];
                    $contentHtml = '<video controls preload="metadata" src="' . htmlspecialchars($vurl, ENT_QUOTES) . '"></video>' . (string)$contentHtml;
                }
            }

            // Upsert Article theo URL
            $slugForArticle = $this->uniqueSlug($title);
            $article = Article::updateOrCreate(
                ['url' => $this->url],
                [
                    'genre_id'         => $genre->id,
                    'title'            => $title,
                    'slug'             => $slugForArticle,
                    'excerpt'          => $excerpt,
                    'content'          => $contentHtml,
                    'thumbnail'        => $thumb,
                    'meta_title'       => $metaTitle,
                    'meta_description' => $metaDescription,
                    'meta_keywords'    => $metaKeywords,
                    'highlight'        => 0,
                    'hidden'           => 0,
                    'copyright'        => 'xoso.com.vn',
                    'author'           => $author,
                    'copy_at'          => optional($publishedAt)->toDateTimeString(),
                    'published_at'     => $publishedAt,
                ]
            );

            // Loại bài
            $article->type = $isVideo ? 'video' : 'text';

            // Avatar từ thumbnail
            if (!empty($thumb)) {
                $avatarFilename = ($article->slug ?: \Illuminate\Support\Str::slug($title ?? 'avatar')) . '-avatar.webp';
                $avatarRelative = downloadImage($thumb, $avatarFilename, true);
                if ($avatarRelative) $article->avatar = $avatarRelative;
            }
            $article->save();

            // Genre pivot (đảm bảo gắn genre theo cateId)
            $article->genres()->syncWithoutDetaching([$genre->id]);

            // Replace ảnh trong content
            if (!empty($article->content)) {
                $replacedHtml = $this->downloadAndReplaceImages($article->content, $title, $article->id);
                if ($replacedHtml) {
                    $article->content = $replacedHtml;
                    $article->save();
                    \Illuminate\Support\Facades\Cache::forget("article:{$article->slug}");
                }
            }

            // Lưu video media
            $savedVideo = 0;
            if ($isVideo && !empty($videoList)) {
                $pos = 0;
                foreach ($videoList as $v) {
                    $pos++;
                    Media::updateOrCreate(
                        ['article_id' => $article->id, 'original_url' => $v['url']],
                        [
                            'type'        => $v['type'],
                            'stored_path' => null,
                            'filename'    => null,
                            'position'    => $pos,
                            'meta'        => null,
                        ]
                    );
                    $savedVideo++;
                }
            }

            Log::info('Saved XOSO article', [
                'id'           => $article->id,
                'type'         => $article->type,
                'video_hint'   => $this->isVideoHint,
                'video_found'  => $foundVideo,
                'videos_saved' => $savedVideo,
                'title'        => mb_substr((string)$article->title, 0, 120),
                'url'          => $this->url,
                'proxy'        => $useProxy,
                'cateId'       => $this->cateId,
                'genre_id'     => $genre->id,
                'genre_slug'   => $genre->slug ?? null,
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $isTransient = str_contains($msg, 'timed out')
                || str_contains($msg, 'Timeout')
                || str_contains($msg, 'Failed to connect')
                || str_contains($msg, 'Connection reset')
                || str_contains($msg, 'cURL error')
                || str_contains($msg, 'SSL');

            if ($isTransient) {
                Log::warning('Transient error → release', ['url' => $this->url, 'err' => $msg]);
                $this->release(90);
                return;
            }

            Log::error('CrawlXosoArticleJob error', ['url' => $this->url, 'err' => $msg, 'cateId' => $this->cateId]);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('CrawlXosoArticleJob FAILED', [
            'url' => $this->url,
            'err' => $e->getMessage(),
            'cateId' => $this->cateId,
        ]);
    }

    /* ====== GENRE RESOLVER (THEO cateId) ====== */
    private function resolveGenre(?int $cateId): ?Genre
    {
        // Map cateId -> slug theo yêu cầu
        $map = [
            404 => 'tin-xo-so',
            418 => 'quy-dinh-xo-so',
            417 => 'tin-trung-thuong',
        ];

        // Ưu tiên cateId nếu có map
        if ($cateId && isset($map[$cateId])) {
            $slug = $map[$cateId];
            $g = Genre::where('slug', $slug)->first();
            if ($g) return $g;
            // Fallback theo name (phòng trường hợp seed khác slug)
            $nameFallbacks = [
                'tin-xo-so'        => 'Tin Xổ Số',
                'quy-dinh-xo-so'   => 'Quy định xổ số',
                'tin-trung-thuong' => 'Tin trúng thưởng',
            ];
            if (isset($nameFallbacks[$slug])) {
                $g = Genre::where('name', $nameFallbacks[$slug])->first();
                if ($g) return $g;
            }
        }

        // Không có cateId → mặc định tin-xo-so
        $g = Genre::where('slug', 'tin-xo-so')->first();
        if ($g) return $g;

        // Fallback theo ID cũ hoặc genre đầu tiên
        return Genre::find(2) ?? Genre::query()->first();
    }

    /* ===== Helpers gốc: GIỮ NGUYÊN CHỮ KÝ ===== */

    private function uniqueSlug(?string $title): string
    {
        $base = Str::slug($title ?? Str::random(8));
        $slug = $base;
        $i = 1;
        while (Article::where('slug', $slug)->where('url', '!=', $this->url)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function normalizeContentHtml(?string $html): ?string
    {
        if ($html === null) return null;
        $html = preg_replace_callback('/<img([^>]+)>/i', function ($m) {
            $tag = $m[0];
            if (preg_match('/\bdata-(?:original|src)="([^"]+)"/i', $tag, $mm)) {
                $src = html_entity_decode($mm[1], ENT_QUOTES);
                $tag = preg_replace('/\ssrc="[^"]*"/i', '', $tag);
                $tag = preg_replace('/\sdata-[a-z0-9\-_]+="[^"]*"/i', '', $tag);
                $tag = preg_replace('/<img/i', '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '"', $tag, 1);
            }
            $tag = preg_replace('/\sdata-[a-z0-9\-_]+="[^"]*"/i', '', $tag);
            $tag = preg_replace_callback('/\sclass="([^"]*)"/i', function ($cm) {
                $cls = trim(preg_replace('/\b(?:lazy|lazy-loaded)\b/', '', $cm[1]));
                return $cls !== '' ? ' class="' . $cls . '"' : '';
            }, $tag, 1);
            return $tag;
        }, $html);
        $html = preg_replace('#<source\b[^>]*>#i', '', $html);
        $html = preg_replace('#<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>#i', '', $html);
        return $html;
    }

    private function firstImageFromContent(?Crawler $contentNode): ?string
    {
        if (!$contentNode || !$contentNode->count()) return null;
        if ($contentNode->filter('img[data-original]')->count()) {
            return $contentNode->filter('img[data-original]')->first()->attr('data-original');
        }
        if ($contentNode->filter('picture img[src]')->count()) {
            return $contentNode->filter('picture img')->first()->attr('src');
        }
        if ($contentNode->filter('img[src]')->count()) {
            return $contentNode->filter('img')->first()->attr('src');
        }
        return null;
    }

    private function downloadAndReplaceImages(string $html, ?string $title, int $articleId): ?string
    {
        return (function ($html, $title, $articleId) {
            $mapOldToNew = [];
            $seen = [];
            $i = 0;
            $baseName = Str::slug($title ?? 'image');
            $urls = [];
            if (preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $m1)) foreach ($m1[1] as $u) $urls[] = $u;
            if (preg_match_all('/<(?:img|source)[^>]+srcset="([^"]+)"/i', $html, $m2)) {
                foreach ($m2[1] as $set) foreach (explode(',', $set) as $part) {
                    $u = trim(preg_split('/\s+/', trim($part))[0] ?? '');
                    if ($u !== '') $urls[] = $u;
                }
            }
            if (empty($urls)) return $html;
            foreach ($urls as $u) {
                $raw = trim($u);
                if ($raw === '' || str_starts_with($raw, 'data:image')) continue;
                $decoded = html_entity_decode($raw, ENT_QUOTES);
                if (isset($seen[$decoded])) continue;
                $seen[$decoded] = true;
                $absUrl = $this->toAbsoluteUrl($decoded, 'https://xoso.com.vn');
                $i++;
                $filename = "{$baseName}-{$i}.webp";
                $storedRelative = downloadImage($absUrl, $filename, false);
                if (!$storedRelative) {
                    $i--;
                    continue;
                }
                Media::updateOrCreate(
                    ['article_id' => $articleId, 'original_url' => $absUrl],
                    ['type' => 'image', 'stored_path' => $storedRelative, 'filename' => basename($storedRelative), 'position' => $i, 'meta' => null]
                );
                $publicUrl = Storage::url($storedRelative);
                $mapOldToNew[$raw]     = $publicUrl;
                $mapOldToNew[$decoded] = $publicUrl;
            }
            if (!empty($mapOldToNew)) {
                $html = preg_replace_callback(
                    '/\b(?:src|srcset|data-original|data-src|data-large|poster|href)\s*=\s*"([^"]+)"/i',
                    function ($m) use ($mapOldToNew) {
                        $val = $m[1];
                        $dec = html_entity_decode($val, ENT_QUOTES);
                        if (isset($mapOldToNew[$val])) return str_replace($val, $mapOldToNew[$val], $m[0]);
                        if (isset($mapOldToNew[$dec])) {
                            $rep = htmlspecialchars($mapOldToNew[$dec], ENT_QUOTES);
                            return str_replace($val, $rep, $m[0]);
                        }
                        return $m[0];
                    },
                    $html
                );
            }
            return $html;
        })($html, $title, $articleId);
    }

    private function extractVideosWide(Crawler $crawler, string $base): array
    {
        $videos = [];
        $crawler->filter('iframe[src], .video-detail__media iframe')->each(function (Crawler $f) use (&$videos, $base) {
            $src = trim((string)$f->attr('src'));
            if (!$src) return;
            $vid = $f->attr('data-vnnembedid');
            if ($vid && preg_match('/^\d+$/', $vid)) $src = "https://embed.2sao.vn/v/{$vid}.html";
            $srcAbs = $this->toAbsoluteUrl($src, $base);
            if ($this->isLikelyVideoUrl($srcAbs)) $videos[] = ['url' => $srcAbs, 'type' => 'embed'];
        });
        $crawler->filter('video')->each(function (Crawler $v) use (&$videos, $base) {
            $src = $v->attr('src');
            if ($src) {
                $u = $this->toAbsoluteUrl($src, $base);
                if ($this->isLikelyVideoUrl($u)) $videos[] = ['url' => $u, 'type' => 'mp4'];
            }
            $v->filter('source[src]')->each(function (Crawler $s) use (&$videos, $base) {
                $src = $s->attr('src');
                if ($src) {
                    $u = $this->toAbsoluteUrl($src, $base);
                    if ($this->isLikelyVideoUrl($u)) $videos[] = ['url' => $u, 'type' => 'mp4'];
                }
            });
        });
        foreach (['data-video', 'data-src', 'data-url', 'data-vnnembedid'] as $attr) {
            $crawler->filter("*[$attr]")->each(function (Crawler $n) use (&$videos, $attr, $base) {
                $val = trim((string)$n->attr($attr));
                if ($val === '') return;
                if ($attr === 'data-vnnembedid' && preg_match('/^\d+$/', $val)) {
                    $u = "https://embed.2sao.vn/v/{$val}.html";
                    if ($this->isLikelyVideoUrl($u)) $videos[] = ['url' => $u, 'type' => 'embed'];
                    return;
                }
                $u = $this->toAbsoluteUrl($val, $base);
                if ($this->isLikelyVideoUrl($u)) $videos[] = ['url' => $u, 'type' => (str_contains(strtolower($u), '.mp4') ? 'mp4' : 'embed')];
            });
        }
        $uniq = [];
        $out = [];
        foreach ($videos as $v) {
            if (isset($uniq[$v['url']])) continue;
            $uniq[$v['url']] = true;
            $out[] = $v;
        }
        return [$out, !empty($out)];
    }

    private function isLikelyVideoUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'javascript:') || str_starts_with($url, 'about:')) return false;
        if (str_contains(strtolower($url), '.mp4')) return true;
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) return false;
        $host = strtolower($parts['host']);
        foreach (['googletagmanager.com', 'google-analytics.com', 'doubleclick.net', 'facebook.com', 'connect.facebook.net'] as $b)
            if (str_contains($host, $b)) return false;
        foreach (
            [
                'youtube.com',
                'www.youtube.com',
                'youtu.be',
                'player.vimeo.com',
                'vimeo.com',
                'dailymotion.com',
                'www.dailymotion.com',
                'embed.vietnamnet.vn',
                'video.vietnamnet.vn',
                'vnncdn',
                'embed.2sao.vn'
            ] as $h
        ) if (str_contains($host, $h)) return true;
        return false;
    }

    private function toAbsoluteUrl(?string $url, string $base): ?string
    {
        if (!$url) return null;
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        if (str_starts_with($url, '//')) return 'https:' . $url;
        if (str_starts_with($url, '/')) return rtrim($base, '/') . $url;
        return $url;
    }

    private function rewriteAnchorDomains(?string $html, array $sourceDomains, ?string $localBase = null): ?string
    {
        if ($html === null || $html === '') return $html;

        $localBase = rtrim($localBase ?: (config('app.domain') ?: ''), '/');
        if ($localBase === '') return $html; // không có base để thay

        // ===== Chuẩn bị matcher cho host nguồn (match cả subdomain) =====
        $hostPatterns = array_map(function ($d) {
            $d = preg_quote($d, '/');
            return '/(?:^|\.)' . $d . '$/i';
        }, $sourceDomains);

        $isSourceHost = function (?string $host) use ($hostPatterns): bool {
            if (!$host) return false;
            foreach ($hostPatterns as $p) {
                if (preg_match($p, $host)) return true;
            }
            return false;
        };

        $localHost = parse_url($localBase, PHP_URL_HOST);

        // ===== Dùng DOM để sửa cả href lẫn inner text =====
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        /** @var \DOMElement $a */
        foreach ($xpath->query('//a[@href]') as $a) {
            $origAttr = $a->getAttribute('href');
            $decoded  = html_entity_decode($origAttr, ENT_QUOTES);

            // Bỏ qua anchor nội bộ, mailto, tel, javascript, data
            if (
                $decoded === '' || $decoded[0] === '#' ||
                preg_match('/^(?:mailto|tel|javascript|data):/i', $decoded)
            ) {
                continue;
            }

            // Chuẩn hoá URL để parse
            $u = $decoded;
            if (str_starts_with($u, '//')) $u = 'https:' . $u; // protocol-relative

            $parts = @parse_url($u);
            if (!$parts) continue;

            $host = $parts['host'] ?? null;
            $path = $parts['path'] ?? '/';
            $qry  = isset($parts['query']) ? ('?' . $parts['query']) : '';
            $frag = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

            // URL tương đối (không host) → bỏ qua
            if (!$host) continue;

            // Không thay nếu đã là localBase
            if ($localHost && strcasecmp($host, $localHost) === 0) continue;

            // Chỉ thay khi host thuộc domain nguồn
            if (!$isSourceHost($host)) continue;

            // ===== Tạo URL mới và set lại href =====
            $relative = '/' . ltrim($path, '/');
            $newUrl   = $localBase . $relative . $qry . $frag;
            $a->setAttribute('href', $newUrl);

            // ===== Cập nhật text hiển thị trong <a> nếu có chứa URL/host cũ =====
            $urlNoScheme    = preg_replace('/^https?:/i', '', $u); // //example.com/path...
            $relativeWithQF = $relative . $qry . $frag;

            // closure đệ quy — phải self-reference bằng use (&$replaceInText)
            $replaceInText = null;
            $replaceInText = function (\DOMNode $node, array $map) use (&$replaceInText) {
                if ($node->nodeType === XML_TEXT_NODE) {
                    $txt = $node->nodeValue ?? '';
                    foreach ($map as $search => $replace) {
                        if ($search === '') continue;
                        $txt = str_replace($search, $replace, $txt);
                    }
                    $node->nodeValue = $txt;
                    return;
                }
                // duyệt text node con
                foreach (iterator_to_array($node->childNodes ?? []) as $child) {
                    $replaceInText($child, $map);
                }
            };

            // Lập map thay thế “thông minh”
            $replacements = [
                $decoded     => $relativeWithQF,          // như người dùng viết trong HTML
                $u           => $relativeWithQF,          // chuẩn hoá có scheme
                $urlNoScheme => $relativeWithQF,          // //host/path...
                $host        => $localHost ?? $host,      // host cũ → host local
            ];

            $replaceInText($a, $replacements);
        }

        // Xuất lại HTML
        $out = $dom->saveHTML();
        return $out;
    }
}
