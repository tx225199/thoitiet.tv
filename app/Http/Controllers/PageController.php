<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Genre;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpClient\HttpClient;

class PageController extends Controller
{
    public function article(Request $request, string $slug)
    {
        $article = Article::with(['genre:id,slug,name', 'tags:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        $genre = $article->genre;

        // tăng view (best-effort) - nếu DB chưa có cột views thì ignore
        try {
            $article->increment('views');
        } catch (\Throwable $e) {
        }

        // Related
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

        // Meta + URL share
        $canonical = route('article', ['slug' => $article->slug]);
        $shareUrl  = $canonical;

        $title = $article->meta_title ?: $article->title;
        $desc  = $article->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?: $article->content), 160);
        $img   = asset_media($article->avatar ?: ($article->thumbnail ?? ''));
        $keywords = $article->meta_keywords ?? '';

        return view('site.article', [
            'genre'   => $genre,
            'article' => $article,
            'related' => $related,
            'meta'    => compact('title', 'desc', 'img', 'canonical', 'keywords'),
            'shareUrl' => $shareUrl,
        ]);
    }

    public function genre(Request $request, string $slug)
    {
        $genre = Genre::query()
            ->where('hidden', 0)
            ->where('slug', $slug)
            ->firstOrFail();

        // ===== DB articles (như trước) =====
        $baseQuery = Article::query()
            ->published()
            ->whereHas('genres', fn($q) => $q->where('genres.id', $genre->id))
            ->orderByDesc('published_at');

        $featured = (clone $baseQuery)
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'avatar', 'thumbnail', 'published_at']);

        $featuredMain = $featured->get(0);
        $featuredSide = $featured->slice(1, 2)->values();

        $featuredIds = $featured->pluck('id')->filter()->all();

        $articlesList = (clone $baseQuery)
            ->when(!empty($featuredIds), fn($q) => $q->whereNotIn('articles.id', $featuredIds))
            ->paginate(12);

        $service = new SiteService();
        $upstreamBase = 'https://thoitiet.tv';

        $upstreamUrl = 'https://thoitiet.tv/tin-tong-hop';

        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout'     => 12,
            'headers'     => [
                'User-Agent'      => $request->userAgent() ?: 'Mozilla/5.0',
                'Accept-Language' => 'vi,en-US;q=0.9',
                'Accept-Encoding' => 'identity',
                'Referer'         => $upstreamBase . '/',
            ],
        ]);

        try {
            $res  = $client->request('GET', $upstreamUrl);
            $html = $res->getContent(false);
        } catch (\Throwable $e) {
            $html = '';
        }

        $boxCategorySidebarWeather = $html
            ? $service->extractBoxCategorySidebarWeather($html, $upstreamBase)
            : '';

        return view('site.genre', [
            'genre'                    => $genre,
            'featuredMain'             => $featuredMain,
            'featuredSide'             => $featuredSide,
            'articlesList'             => $articlesList,
            'boxCategorySidebarWeather' => $boxCategorySidebarWeather,
        ]);
    }
}
