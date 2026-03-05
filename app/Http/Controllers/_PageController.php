<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Article;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class PageController extends Controller
{
    public function genre(Request $request, $slug)
    {
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
            ->take(4)
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

        // Tin mới (box phải)
        $newNews = Article::with('genre:id,slug,name')
            ->published()
            ->where('genre_id', $genre->id)
            ->orderByDesc('highlight')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('site.genre', [
            'genre'      => $genre,
            'topBig'     => $topBig,
            'topSmall4'  => $topSmall4,
            'list'       => $paginator,
            'newNews'    => $newNews,
        ]);
    }


    public function article(Request $request, $slug)
    {
        // Lấy bài theo slug (không cần genreSlug nữa)
        $article = Article::with(['genre:id,slug,name', 'tags:id,name,slug'])
            ->published()
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

        // Tin nổi bật / cùng chuyên mục
        $hotBlock = collect();
        if ($genre) {
            $hotBlock = Article::with('genre:id,slug,name')
                ->published()
                ->inAnyGenre($genre->id)
                ->where('id', '<>', $article->id)
                ->orderByDesc('published_at')->orderByDesc('id')
                ->take(5)
                ->get();
        }

        // ===== AJAX load-more (cùng chuyên mục) =====
        if ($request->ajax() || $request->boolean('ajax')) {
            $page     = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('pageSize', 10);
            $exclude  = collect(explode(',', (string) $request->get('exclude')))
                ->filter()->map(fn($v) => (int) $v)->values()->all();

            $exclude = array_values(array_unique(array_merge($exclude, [$article->id])));

            $pager = Article::with('genre:id,slug,name')
                ->published()
                ->when($genre, fn($qq) => $qq->inAnyGenre($genre->id))
                ->whereNotIn('id', $exclude)
                ->orderByDesc('published_at')->orderByDesc('id')
                ->paginate($pageSize, ['*'], 'page', $page);

            $html = view('site.partials._article_horizontal', [
                'items' => $pager->items(),
                'genre' => $genre,
            ])->render();

            return response()->json([
                'status'   => true,
                'html'     => $html,
                'hasMore'  => $pager->hasMorePages(),
                'nextPage' => $pager->currentPage() + 1,
            ]);
        }

        // Meta
        $title = $article->meta_title ?: $article->title;
        $desc  = $article->meta_description ?: Str::limit(strip_tags($article->excerpt ?: $article->content), 160);
        $img   = asset_media($article->avatar ?: ($article->thumbnail ?? ''));

        $mostRead = Article::query()
            ->published()
            ->with('genre:id,slug,name')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $excludeIds = $hotBlock->pluck('id')->push($article->id)->implode(',');

        return view('site.article', [
            'genre'      => $genre,
            'article'    => $article,
            'related'    => $related,
            'hotBlock'   => $hotBlock,
            'excludeIds' => $excludeIds,
            'meta'       => compact('title', 'desc', 'img'),
            'mostRead'   => $mostRead,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return redirect('/');
        }

        $perPage = (int) $request->get('pageSize', 10);
        $page    = (int) $request->get('page', 1);

        // Xây query base
        $base = Article::with('genre:id,slug,name')
            ->published();

        $useFulltext = true;
        try {
            $fulltext = (clone $base)
                ->selectRaw('articles.*, MATCH(title, excerpt, content) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance', [$q])
                ->whereRaw('MATCH(title, excerpt, content) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q])
                ->orderByDesc('relevance')
                ->orderByDesc('published_at')
                ->orderByDesc('id');
            $fulltext->limit(1)->first();
            $queryMethod = 'fulltext';
        } catch (\Throwable $e) {
            $useFulltext = false;
            $queryMethod = 'like';
        }

        $searchQuery = $useFulltext
            ? (clone $base)
            ->selectRaw('articles.*, MATCH(title, excerpt, content) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance', [$q])
            ->whereRaw('MATCH(title, excerpt, content) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q])
            ->orderByDesc('relevance')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            : (clone $base)
            ->where(function ($x) use ($q) {
                $x->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $topBig = (clone $searchQuery)->first();

        $exclude = $topBig ? [$topBig->id] : [];
        $topSmall4 = (clone $searchQuery)
            ->when(!empty($exclude), fn($q) => $q->whereNotIn('id', $exclude))
            ->take(4)
            ->get();

        $excludeIds = collect([$topBig])->filter()->pluck('id')
            ->merge($topSmall4->pluck('id'))
            ->all();

        $paginator = (clone $searchQuery)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->paginate($perPage);

        if ($request->ajax() || $request->get('ajax')) {
            $html = view('site.partials._article_horizontal', [
                'items' => $paginator->items(),
                'genre' => null,
            ])->render();

            return response()->json([
                'status'   => true,
                'html'     => $html,
                'hasMore'  => $paginator->hasMorePages(),
                'nextPage' => $paginator->currentPage() + 1,
            ]);
        }

        $newNews = Article::with('genre:id,slug,name')
            ->published()
            ->orderByDesc('published_at')->orderByDesc('id')
            ->take(7)->get();

        return view('site.search', [
            'q'          => $q,
            'topBig'     => $topBig,
            'topSmall4'  => $topSmall4,
            'list'       => $paginator,
            'newNews'    => $newNews,
        ]);
    }

    public function contact()
    {
        $page = Page::where('slug', 'contact')->firstOrFail();

        $meta = [
            'title' => $page->meta_title ?: $page->title,
            'desc'  => $page->meta_description ?: '',
            'img'   => '', 
        ];

        $mostRead = Article::query()
            ->published()
            ->with('genre:id,slug,name')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return view('site.pages.contact', compact('page', 'meta', 'mostRead'));
    }

    public function term()
    {
        $page = Page::where('slug', 'term')->firstOrFail();

        $meta = [
            'title' => $page->meta_title ?: $page->title,
            'desc'  => $page->meta_description ?: '',
            'img'   => '',
        ];

        $mostRead = Article::query()
            ->published()
            ->with('genre:id,slug,name')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return view('site.pages.term', compact('page', 'meta', 'mostRead'));
    }
}
