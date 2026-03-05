{{-- resources/views/site/partials/_article_horizontal.blade.php --}}
@php use Illuminate\Support\Str; @endphp

@foreach(($items ?? []) as $a)
    @php
        $href = route('article', [$a->slug]);
        $img  = asset_media($a->avatar ?: ($a->thumbnail ?? ''));
        $sum  = $a->excerpt ?: Str::limit(strip_tags($a->content ?? ''), 200);
    @endphp

    <article class="article-list">
        <header>
            <h3 class="article-title">
                <a href="{{ $href }}" title="{{ $a->title }}">{{ $a->title }}</a>
            </h3>
        </header>
        <a class="article-image thumb200" href="{{ $href }}" title="{{ $a->title }}">
            <img alt="{{ $a->title }}" class="image" src="{{ $img }}">
        </a>
        @if($sum)
            <div class="article-summary">{!! $sum !!}</div>
        @endif
    </article>
@endforeach
