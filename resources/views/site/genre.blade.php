@extends('site.master')

@section('head')
    @php
        $siteName = 'Dự báo thời tiết 63 tỉnh và thành phố chính xác nhất Việt Nam';

        $title = ($genre->name ?? 'Tin tức') . ' | ' . $siteName;

        $desc =
            $genre->meta_description ??
            ($genre->description ??
                'Website cập nhật tình hình dự báo thời tiết từng ngày, từng giờ. Diễn biến thời tiết các tỉnh thành, quận huyện ở Việt Nam.');

        // ảnh OG: ưu tiên setting / genre (nếu có), fallback giống gốc
        $ogImage = $ogImage ?? 'https://thoitiet.tv/uploads/images/setting/huyhoang/2023/09/25/csmxh-1695636686.jpg';

        $canonical = route('genre', ['slug' => $genre->slug]); // /{slug}.html
    @endphp

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $desc }}">
    <meta name="robots" content="index,follow">
    <meta name="google-site-verification" content="7pTgpatVr03nepCHbCb1GsiRKL8QQgO0cm78IWB74R8">
    <meta name="author" content="thoitiet.tv">

    <link rel="alternate" hreflang="vi-vn" href="{{ url('/') }}" />
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:site_name" content="{{ $siteName }}" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="vi_VN" />
    <meta property="og:locale:alternate" content="vi_VN" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $desc }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta property="og:image:height" content="315" />
    <meta property="og:image:width" content="600" />

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'WebPage',
        'description' => $desc,
        'url' => $canonical,
        'image' => $ogImage,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => collect($genres ?? [])
            ->take(3)
            ->values()
            ->map(fn($g, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $g->name,
                'item' => route('genre', ['slug' => $g->slug]),
            ])
            ->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('main')
    <div class="section-content" bis_skin_checked="1">
        <div class="promotion-sticky pc-sticky-left" bis_skin_checked="1"></div>
        <div class="category-bar-wrapper category-title text-center" bis_skin_checked="1">
            <div class="container" bis_skin_checked="1">
                <div class="row" bis_skin_checked="1">
                    <div class="category-bar" bis_skin_checked="1">
                        <ul class="category-sub-cat"></ul>
                        <div class="input-group news_title_category flex-wrap" bis_skin_checked="1">
                            @php
                                $activeSlug = $genre->slug ?? '';
                            @endphp

                            <div class="input-group">
                                @foreach ($genres as $g)
                                    <a rel="dofollow" href="{{ route('genre', ['slug' => $g->slug]) }}"
                                        class="input-group-text border-0 rounded-0 pt-2 px-4 font_w500 me-0 title_slider d-md-block {{ $g->slug === $activeSlug ? 'active' : '' }}">
                                        {{ $g->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-4 section-content" bis_skin_checked="1">
            <div class="container" bis_skin_checked="1">
                <div class="row" bis_skin_checked="1">
                    <div class="col col-main col-xl-9 order-xl-1 col-lg-9 order-lg-1 col-md-12 col-sm-12 col-12 w7">
                        {{-- FEATURED --}}
                        <div class="box-featured-article row">
                            {{-- MAIN --}}
                            <div class="tieudiem main col-md-8">
                                @if ($featuredMain)
                                    @php
                                        $href = route('article', ['slug' => $featuredMain->slug]);
                                        $img = asset_media($featuredMain->avatar);

                                        $desc = $featuredMain->excerpt ?: '';
                                    @endphp

                                    <div class="box-news box-news-larger kind-van-ban">
                                        <div class="image image-wrapper">
                                            <a href="{{ $href }}" title="{{ $featuredMain->title }}"
                                                class="image image-medium">
                                                @if ($img)
                                                    <img src="{{ $img }}" alt="{{ $featuredMain->title }}"
                                                        class="lazy entered loaded">
                                                @endif
                                            </a>
                                        </div>

                                        <div class="content">
                                            <h2 class="title f-rsb fs6">
                                                <a href="{{ $href }}"
                                                    title="{{ $featuredMain->title }}">{{ $featuredMain->title }}</a>
                                            </h2>

                                            <p class="meta-news">
                                                <span class="time-public">
                                                    {{ optional($featuredMain->published_at)->format('H:i d/m/Y') }}
                                                </span>
                                            </p>

                                            @if ($desc)
                                                <p class="description">
                                                    {!! \Illuminate\Support\Str::limit(strip_tags($desc), 220) !!}
                                                    <a href="{{ $href }}" title="{{ $featuredMain->title }}"></a>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- SIDE 2 items --}}
                            <div class="box-related side col-md-4">
                                @foreach ($featuredSide ?? collect() as $a)
                                    @php
                                        $href = route('article', ['slug' => $a->slug]);
                                        $img = asset_media($a->avatar);
                                    @endphp

                                    <div class="box-news full-width">
                                        <div class="image image-wrapper">
                                            <a href="{{ $href }}" title="{{ $a->title }}"
                                                class="image image-small">
                                                @if ($img)
                                                    <img src="{{ $img }}" alt="{{ $a->title }}"
                                                        class="lazy entered loaded">
                                                @endif
                                            </a>
                                        </div>
                                        <div class="content">
                                            <h2 class="title f-rsb fs4">
                                                <a href="{{ $href }}"
                                                    title="{{ $a->title }}">{{ $a->title }}</a>
                                            </h2>
                                            <p class="meta-news">
                                                <span
                                                    class="time-public">{{ optional($a->published_at)->format('H:i d/m/Y') }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="clearfix"></div>
                        </div>

                        <div class="w-separator"></div>

                        {{-- LIST --}}
                        <div class="box-other-articles full-width">
                            @foreach ($articlesList as $a)
                                @php
                                    $href = route('article', ['slug' => $a->slug]);
                                    $img = asset_media($a->avatar);
                                    $desc = $a->excerpt ?: '';
                                @endphp

                                <div class="item-news kind-van-ban item-news-common">
                                    <div class="style_img_left match-height">
                                        <div class="image image-wrapper">
                                            <a href="{{ $href }}" title="{{ $a->title }}"
                                                class="image image-small">
                                                @if ($img)
                                                    <img src="{{ $img }}" alt="{{ $a->title }}"
                                                        class="lazy entered loaded">
                                                @endif
                                            </a>
                                        </div>

                                        <div class="content">
                                            <h3 class="title-news title">
                                                <a href="{{ $href }}"
                                                    title="{{ $a->title }}">{{ $a->title }}</a>
                                            </h3>

                                            <div class="clearfix"></div>

                                            @if ($desc)
                                                <div class="description">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($desc), 240) }}
                                                </div>
                                            @endif

                                            <p class="meta-news">
                                                <span class="time-public">
                                                    {{ optional($a->published_at)->format('H:i d/m/Y') }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        {{ $articlesList->appends(request()->input())->links('site.widgets.pagination') }}
                    </div>

                    <aside class="col col-right col-xl-3 order-xl-2 col-lg-3 order-lg-2 col-md-12 col-sm-12 col-12 w3">

                        {!! $boxCategorySidebarWeather !!}

                        <section class="section section_container box-category">
                            <div class="row" bis_skin_checked="1"></div>
                        </section>
                        <div class="widget" style="position: sticky; top: 80px;" bis_skin_checked="1"></div>
                    </aside>

                </div>
            </div>
        </div>
        <div class="promotion-sticky pc-sticky-right" bis_skin_checked="1"></div>
    </div>
@endsection
