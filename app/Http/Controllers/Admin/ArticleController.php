<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\Genre;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::query()
            ->with([
                'genre:id,name,slug',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->latest('id');

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->input('search')));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(slug)  LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->filled('genre_id') && $request->genre_id !== 'all') {
            $query->where('genre_id', (int) $request->genre_id);
        }

        $data = $query->paginate(20);

        return view('admin.articles.index', [
            'data'    => $data,
            'genres'  => Genre::orderBy('sort')->get(['id', 'name']),
            'request' => $request,
        ]);
    }

    public function scheduled(Request $request)
    {
        $query = Article::query()
            ->with([
                'genre:id,name,slug',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->latest('published_at');

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->input('search')));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(slug)  LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->filled('genre_id') && $request->genre_id !== 'all') {
            $query->where('genre_id', (int) $request->genre_id);
        }

        $data = $query->paginate(20);

        return view('admin.articles.scheduled', [
            'data'    => $data,
            'genres'  => Genre::orderBy('sort')->get(['id', 'name']),
            'request' => $request,
        ]);
    }

    public function hidden(Request $request)
    {
        $query = Article::query()
            ->with([
                'genre:id,name,slug',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->where('hidden', 1)
            ->latest('id');

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->input('search')));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(slug)  LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->filled('genre_id') && $request->genre_id !== 'all') {
            $query->where('genre_id', (int) $request->genre_id);
        }

        $data = $query->paginate(20);

        return view('admin.articles.hidden', [
            'data'    => $data,
            'genres'  => Genre::orderBy('sort')->get(['id', 'name']),
            'request' => $request,
        ]);
    }


    public function create()
    {
        return view('admin.articles.form', [
            'article' => null,
            'genres'  => Genre::where(['hidden' => 0])->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255|unique:articles,title',
            'slug'             => 'nullable|string|max:255|unique:articles,slug',
            'genre_id'         => 'nullable|exists:genres,id',
            'excerpt'          => 'nullable|string',
            'content'          => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords'    => 'nullable|string|max:255',
            'highlight'        => 'nullable|in:0,1',
            'hidden'           => 'nullable|in:0,1',
            'published_at'     => 'nullable|date',
            'url'              => 'nullable|string|max:255',
            'copyright'        => 'nullable|string|max:255',
            'copy_at'          => 'nullable|string|max:255',
            'avatar_file'      => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        $article = new Article();
        $article->title            = $request->title;
        $article->slug             = $request->slug ?: Str::slug($request->title);
        $article->genre_id         = $request->genre_id;
        $article->excerpt          = $request->excerpt;
        $article->meta_title       = $request->meta_title;
        $article->meta_description = $request->meta_description;
        $article->meta_keywords    = $request->meta_keywords;
        $article->highlight        = (int) ($request->highlight ?? 0);
        $article->hidden           = (int) ($request->hidden ?? 0);
        $article->published_at     = $request->published_at;
        $article->url              = $request->url;
        $article->copyright        = $request->copyright;
        $article->copy_at          = $request->copy_at;
        $article->post_type        = 'manual';

        // Avatar upload -> images/thumbs/{slug}-avatar-*.webp
        if ($request->hasFile('avatar_file')) {
            $savedRelative = uploadImageLocal(
                $request->file('avatar_file'),
                Str::slug($request->title) . '-avatar',
                true // thumbs
            );
            if ($savedRelative) {
                $article->avatar = $savedRelative;
            }
        }

        // Lưu lần 1 để có ID
        $article->save();

        // Sync genre pivot (không ghi đè genre phụ)
        if ($request->filled('genre_id')) {
            $article->genres()->syncWithoutDetaching([$request->genre_id]);
        }

        // ===== Xử lý content từ request =====
        $content = (string) ($request->content ?? '');

        if ($content !== '') {
            $content = removeIframeSandbox($content);

            $content = $this->autoEmbedIfRawUrl($content);

            $replaced = $this->downloadAndReplaceExternalImages($content, $article->title, $article->id);
            if ($replaced !== null) {
                $content = $replaced;
            }

            $article->type    = $this->detectVideoType($content) ? 'video' : 'text';
            $article->content = $content;
        } else {
            $article->type    = 'text';
            $article->content = '';
        }

        $article->created_by = Auth::guard('admin')->user()->id;

        $article->save();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Thêm bài viết thành công!');
    }


    public function edit($id)
    {
        $article = Article::findOrFail($id);

        return view('admin.articles.form', [
            'article' => $article,
            'genres'  => Genre::where(['hidden' => 0])->orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $request->validate([
            'title'            => 'required|string|max:255|unique:articles,title,' . $article->id,
            'slug'             => 'nullable|string|max:255|unique:articles,slug,' . $article->id,
            'genre_id'         => 'nullable|exists:genres,id',
            'excerpt'          => 'nullable|string',
            'content'          => 'nullable|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords'    => 'nullable|string|max:255',
            'highlight'        => 'nullable|in:0,1',
            'hidden'           => 'nullable|in:0,1',
            'published_at'     => 'nullable|date',
            'url'              => 'nullable|string|max:255',
            'copyright'        => 'nullable|string|max:255',
            'copy_at'          => 'nullable|string|max:255',
            'avatar_file'      => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        // ===== 1) Fill các field cơ bản
        $article->title            = $request->title;
        $article->slug             = $request->slug ?: Str::slug($request->title);
        $article->genre_id         = $request->genre_id;
        $article->excerpt          = $request->excerpt;
        $article->meta_title       = $request->meta_title;
        $article->meta_description = $request->meta_description;
        $article->meta_keywords    = $request->meta_keywords;
        $article->highlight        = (int) ($request->highlight ?? 0);
        $article->hidden           = (int) ($request->hidden ?? 0);
        $article->published_at     = $request->published_at;
        $article->url              = $request->url;
        $article->copyright        = $request->copyright;
        $article->copy_at          = $request->copy_at;
        $article->post_type        = $article->post_type;

        // Avatar upload (lưu path tương đối như crawler)
        if ($request->hasFile('avatar_file')) {
            $savedRelative = uploadImageLocal(
                $request->file('avatar_file'),
                Str::slug($request->title) . '-avatar',
                true // -> images/thumbs
            );
            if ($savedRelative) {
                $article->avatar = $savedRelative;
            }
        }

        // Lưu lần 1 để đảm bảo có id trước khi ghi Media (nếu cần)
        $article->save();

        // Đảm bảo genre chính có trong pivot
        if ($request->filled('genre_id')) {
            $article->genres()->syncWithoutDetaching([$request->genre_id]);
        }

        // ===== 2) Xử lý CONTENT từ request (không dùng content cũ)
        $content = (string) ($request->content ?? '');

        if ($content !== '') {
            // a) gỡ sandbox="" khỏi các iframe (giữ nguyên các thuộc tính khác)
            $content = removeIframeSandbox($content);

            // b) nếu người dùng chỉ dán URL video “trần”, tự quấn <iframe> cho domain phổ biến
            $content = $this->autoEmbedIfRawUrl($content);

            // c) tải ảnh ngoài & replace URL thành ảnh nội bộ + lưu Media
            $replaced = $this->downloadAndReplaceExternalImages($content, $article->title, $article->id);
            if ($replaced !== null) {
                $content = $replaced;
            }

            // d) set type theo nội dung cuối cùng
            $article->type    = $this->detectVideoType($content) ? 'video' : 'text';
            $article->content = $content;
        } else {
            $article->type    = 'text';
            $article->content = '';
        }

        $article->updated_by = Auth::guard('admin')->user()->id;

        // Lưu lần cuối
        $article->save();


        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Cập nhật bài viết thành công!');
    }


    public function destroy($id)
    {
        Article::where('id', $id)->delete();
        return redirect()->back()->with('success', 'Xoá bài viết thành công!');
    }

    /* ========================= Helpers ========================= */

    /**
     * Nếu content đã có iframe/video thì giữ nguyên.
     * Nếu chỉ có URL video trần (YouTube/Vimeo/2sao/VNN/.mp4) -> bọc thành iframe/<video>.
     */
    private function autoEmbedIfRawUrl(?string $html): ?string
    {
        if (!$html) return $html;

        // Đã có iframe hoặc video -> không can thiệp
        if (preg_match('#<\s*(iframe|video)\b#i', $html)) {
            return $html;
        }

        // Tìm URL trần trong text
        if (!preg_match_all('#https?://[^\s"<]+#i', $html, $all)) {
            return $html;
        }

        $candidate = null;
        $isMp4     = false;

        $patterns = [
            // youtube
            '#https?://(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_\-]+)#i' => fn($m) => 'https://www.youtube.com/embed/' . $m[1],
            '#https?://youtu\.be/([A-Za-z0-9_\-]+)#i'                       => fn($m) => 'https://www.youtube.com/embed/' . $m[1],
            // vimeo
            '#https?://vimeo\.com/(\d+)#i'                                  => fn($m) => 'https://player.vimeo.com/video/' . $m[1],
            '#https?://player\.vimeo\.com/video/(\d+)#i'                    => fn($m) => $m[0],
            // 2sao/vnn embed
            '#https?://(?:embed\.)?(2sao\.vn|vietnamnet\.vn|video\.vietnamnet\.vn)/[^\s"<]+#i'
            => fn($m) => $m[0],
            // mp4
            '#https?://[^\s"<]+\.mp4(?:\?[^\s"<]*)?#i'                      => fn($m) => ['__MP4__', $m[0]],
        ];

        foreach ($all[0] as $url) {
            foreach ($patterns as $re => $map) {
                if (preg_match($re, $url, $m)) {
                    $mapped = $map($m);
                    if (is_array($mapped) && $mapped[0] === '__MP4__') {
                        $candidate = $mapped[1];
                        $isMp4     = true;
                    } else {
                        $candidate = $mapped;
                    }
                    break 2;
                }
            }
        }

        if (!$candidate) return $html;

        if ($isMp4) {
            $embed = '<figure class="vnn-resposive-video-embed-169"><video controls preload="metadata" src="' .
                htmlspecialchars($candidate, ENT_QUOTES) .
                '"></video></figure>';
            return str_replace($candidate, $embed, $html);
        }

        $iframe = '<figure class="vnn-resposive-video-embed-169"><iframe src="' .
            htmlspecialchars($candidate, ENT_QUOTES) .
            '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></figure>';

        return str_replace($candidate, $iframe, $html);
    }

    /**
     * Tải ảnh ngoài domain về storage và replace URL trong HTML.
     * - Chỉ xử lý các thuộc tính ảnh: src, srcset, data-original, data-src, data-large, poster
     * - Bỏ qua link nội bộ (cùng host với app) và data URI
     * - Chỉ tải các URL có vẻ là ảnh (đuôi jpg/png/gif/webp) để tránh lỗi getimagesize
     */
    private function downloadAndReplaceExternalImages(string $html, ?string $title, int $articleId): ?string
    {
        if ($html === '') return $html;

        $appUrl  = config('app.domain');
        $appHost = null;
        if ($appUrl) {
            $p = parse_url($appUrl);
            $appHost = $p['host'] ?? null;
        }

        $mapOldToNew = [];
        $seen        = [];
        $i           = 0;
        $baseName    = Str::slug($title ?? 'image');
        $urls        = [];

        // <img src="...">
        if (preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $m1)) {
            foreach ($m1[1] as $u) $urls[] = $u;
        }
        // (img|source) srcset="u1 1x, u2 2x"
        if (preg_match_all('/<(?:img|source)[^>]+srcset="([^"]+)"/i', $html, $m2)) {
            foreach ($m2[1] as $set) {
                foreach (explode(',', $set) as $part) {
                    $u = trim(preg_split('/\s+/', trim($part))[0] ?? '');
                    if ($u !== '') $urls[] = $u;
                }
            }
        }
        // data-original|data-src|data-large|poster
        if (preg_match_all('/\b(?:data-original|data-src|data-large|poster)\s*=\s*"([^"]+)"/i', $html, $m3)) {
            foreach ($m3[1] as $u) $urls[] = $u;
        }

        if (empty($urls)) return $html;

        foreach ($urls as $raw) {
            $raw = trim($raw);
            if ($raw === '' || str_starts_with($raw, 'data:image')) continue;

            $decoded = html_entity_decode($raw, ENT_QUOTES);

            // Chỉ http(s)
            if (!preg_match('#^https?://#i', $decoded)) continue;

            // Bỏ qua link nội bộ
            $host = parse_url($decoded, PHP_URL_HOST);
            if ($appHost && $host && strcasecmp($host, $appHost) === 0) continue;

            // Tránh tải nhầm trang HTML: lọc nhanh theo đuôi ảnh
            if (!preg_match('/\.(?:jpe?g|png|gif|webp)(?:[\?#].*)?$/i', $decoded)) continue;

            if (isset($seen[$decoded])) continue;
            $seen[$decoded] = true;

            $i++;
            $filename = "{$baseName}-{$i}.webp";

            // Lưu như crawler (isThumb = false => thư mục posters)
            $storedRelative = downloadImage($decoded, $filename, false);
            if (!$storedRelative) {
                $i--;
                continue;
            }

            // Lưu Media
            Media::updateOrCreate(
                ['article_id' => $articleId, 'original_url' => $decoded],
                [
                    'type'        => 'image',
                    'stored_path' => $storedRelative,
                    'filename'    => basename($storedRelative),
                    'position'    => $i,
                    'meta'        => null,
                ]
            );

            // Public URL để nhúng vào HTML
            $publicUrl = Storage::url($storedRelative);
            $mapOldToNew[$raw]     = $publicUrl; // bản encoder (có thể &amp;)
            $mapOldToNew[$decoded] = $publicUrl; // bản decode
        }

        if (!empty($mapOldToNew)) {
            // Replace lại các thuộc tính ảnh (KHÔNG đụng href)
            $html = preg_replace_callback(
                '/\b(?:src|srcset|data-original|data-src|data-large|poster)\s*=\s*"([^"]+)"/i',
                function ($m) use ($mapOldToNew) {
                    $val = $m[1];
                    $dec = html_entity_decode($val, ENT_QUOTES);
                    if (isset($mapOldToNew[$val])) {
                        return str_replace($val, $mapOldToNew[$val], $m[0]);
                    } elseif (isset($mapOldToNew[$dec])) {
                        $rep = htmlspecialchars($mapOldToNew[$dec], ENT_QUOTES);
                        return str_replace($val, $rep, $m[0]);
                    }
                    return $m[0];
                },
                $html
            );
        }

        return $html;
    }


    /**
     * Phát hiện nội dung có chứa video (iframe nhúng các host phổ biến, thẻ <video>/<source>, hay URL .mp4).
     */
    private function detectVideoType(?string $html): bool
    {
        if (!$html) return false;

        if (preg_match('#<\s*video\b#i', $html)) return true;
        if (preg_match('#<\s*source[^>]+src="[^"]+\.mp4[^"]*"#i', $html)) return true;

        $allowHosts = [
            'youtube.com',
            'www.youtube.com',
            'youtu.be',
            'player.vimeo.com',
            'vimeo.com',
            'embed.2sao.vn',
            'embed.vietnamnet.vn',
            'video.vietnamnet.vn',
            'dailymotion.com',
            'www.dailymotion.com',
        ];
        if (preg_match('#<\s*iframe[^>]+src="([^"]+)"#i', $html, $m)) {
            $src = $m[1] ?? '';
            foreach ($allowHosts as $h) {
                if (stripos($src, $h) !== false) return true;
            }
            if (stripos($src, '.mp4') !== false) return true;
        }

        if (preg_match('#https?://[^\s"<]+\.mp4(\?[^\s"<]*)?#i', $html)) return true;

        return false;
    }

    public function active(Request $request)
    {
        $article = Article::where(['id' => $request->id])->first();

        if (!isset($article)) {
            return response()->json(['error' => true, 'message' => 'Article does not exist']);
        }

        $article->hidden = $request->hidden == "false" ? 1 : 0;
        $article->save();

        return response()->json(['error' => false, 'message' => 'Updated successfully']);
    }

    public function highlight(Request $request)
    {
        $article = Article::find($request->id);

        if (!$article) {
            return response()->json(['error' => true, 'message' => 'Article does not exist']);
        }

        $article->highlight = $request->highlight == "true" ? 1 : 0;
        $article->save();

        return response()->json(['error' => false, 'message' => 'Cập nhật trạng thái ghim thành công']);
    }
}
