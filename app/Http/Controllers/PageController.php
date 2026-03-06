<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function article(Request $request, $slug)
    {
        try {
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
                'genre'      => $genre,
                'article'    => $article,
                'related'    => $related,
                'meta'       => compact('title', 'desc', 'img'),
            ]);
        } catch (\Throwable $e) {
            dd($e);
        }
    }
}
