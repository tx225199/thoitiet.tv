@extends('site.master')

@section('head')
    @php
        $siteName = 'Dự báo thời tiết 63 tỉnh và thành phố chính xác nhất Việt Nam';

        $title = $meta['title'] ?? ($article->meta_title ?: $article->title);
        $desc = $meta['desc'] ?? ($article->meta_description ?: ($article->excerpt ?: $article->title));
        $keywords = $meta['keywords'] ?? ($article->meta_keywords ?? '');

        $canonical = $meta['canonical'] ?? route('article', ['slug' => $article->slug]);
        $img = $meta['img'] ?? asset_media($article->avatar ?: $article->thumbnail ?? '');

        // thời gian publish/modify
        $publishedIso = optional($article->published_at)->toIso8601String();
        $modifiedIso = optional($article->updated_at)->toIso8601String() ?: $publishedIso;

        // author (best effort)
        $authorName = trim($article->createdBy->name ?? 'Admin');
        $authorUrl = $article->createdBy->username ?? null;
        $authorUrl = $authorUrl ? url('/user/' . $authorUrl) : null;

        // publisher
        $publisherName = 'thoitiet247';
        $publisherLogo = '/uploads/images/setting/Mazart/2024/08/14/thoitietvn-1000x500-3-1723606827.png';

        // amp (nếu bạn có route amp thì đổi theo route của bạn, còn không thì để comment)
        $ampUrl = url('/amp/' . $article->slug . '.html');
    @endphp

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $desc }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="robots" content="index,follow">
    <meta name="google-site-verification" content="7pTgpatVr03nepCHbCb1GsiRKL8QQgO0cm78IWB74R8">
    <meta name="author" content="thoitiet.tv">

    @if ($publishedIso)
        <meta name="pubdate" content="{{ $publishedIso }}">
        <meta property="article:published_time" content="{{ $publishedIso }}">
    @endif

    @if ($modifiedIso)
        <meta name="lastmod" content="{{ $modifiedIso }}">
        <meta property="article:modified_time" content="{{ $modifiedIso }}">
    @endif

    <link rel="canonical" href="{{ $canonical }}" />
    <link rel="amphtml" href="{{ $ampUrl }}" />
    <link rel="alternate" hreflang="vi-vn" href="{{ url('/') }}" />

    <meta property="og:site_name" content="{{ $siteName }}" />
    <meta property="og:type" content="article" />
    <meta property="og:locale" content="vi_VN" />
    <meta property="og:locale:alternate" content="vi_VN" />
    <meta property="og:image:alt" content="{{ $title }}" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $desc }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    @if ($img)
        <meta property="og:image" content="{{ $img }}" />
        <meta property="og:image:height" content="315" />
        <meta property="og:image:width" content="600" />
    @endif

    {{-- JSON-LD WebPage --}}
    @php
    $img = asset_media($featuredMain->avatar);
@endphp

<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'WebPage',
  'description' => $desc,
  'url' => $siteName,
  'image' => $img,
  'datePublished' => $publishedIso,
  'dateModified' => $modifiedIso,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

    {{-- JSON-LD NewsArticle --}}
    <script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'NewsArticle',
  'mainEntityOfPage' => [
    '@type' => 'WebPage',
    '@id' => $canonical,
  ],
  'headline' => $title,
  'image' => $img ? [ parse_url($img, PHP_URL_PATH) ?: $img ] : [],
  'datePublished' => $publishedIso,
  'dateModified' => $modifiedIso,
  'publisher' => [
    '@type' => 'Organization',
    'name' => $publisherName,
    'logo' => [
      '@type' => 'ImageObject',
      'url' => $publisherLogo,
    ],
  ],
  'description' => $desc,
  'author' => [
    '@type' => 'Person',
    'name' => $authorName,
    'url' => $authorUrl,
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
    <link href="{{ url('/') }}/themes/tinhte/public/css/detail.css" rel="stylesheet" type="text/css">
@endsection

@section('main')
    <div class="py-3 section-content" bis_skin_checked="1">
        <div class="promotion-sticky pc-sticky-left" bis_skin_checked="1"></div>
        <div class="wraper-content" bis_skin_checked="1">
            <section class="main">
                <div class="container" bis_skin_checked="1">
                    <div class="row" bis_skin_checked="1">
                        <div data-sticky-container=""
                            class="col col-main col-xl-9 order-xl-1 col-lg-9 order-lg-1 col-md-12 col-sm-12 col-12"
                            bis_skin_checked="1">
                            <div class="article-detail" bis_skin_checked="1">
                                <ol itemscope itemtype="http://schema.org/BreadcrumbList" class="breadcrumbs">
                                    <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                                        <a itemprop="item" href="{{ url('/') }}" title="Trang chủ">
                                            <span itemprop="name">Trang chủ</span>
                                        </a>
                                        <meta itemprop="position" content="1">
                                    </li>

                                    @if ($genre)
                                        <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                                            <a itemprop="item" href="{{ route('genre', ['slug' => $genre->slug]) }}"
                                                title="{{ $genre->name }}">
                                                <span itemprop="name">{{ $genre->name }}</span>
                                            </a>
                                            <meta itemprop="position" content="2">
                                        </li>
                                    @endif
                                </ol>

                                <ul class="dt-news__social sticky-social-type-1">
                                    <li>
                                        <a title="Home" href="{{ url('/') }}"
                                            class="dt-social__item dt-social__item--home">
                                            <i aria-hidden="true" class="fa fa-home"></i>
                                        </a>
                                    </li>

                                    <li>
                                        <a target="_blank" title="Chia sẻ lên Facebook"
                                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                            class="dt-social__item dt-social__item--facebook">
                                            <i class="dt-icon icon-facebook"></i>
                                        </a>
                                    </li>

                                    <li>
                                        <a title="Chia sẻ qua Email" href="mailto:?subject={{ urlencode($shareUrl) }}"
                                            class="dt-social__item">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </li>
                                </ul>

                                <div class="dt-news__body" bis_skin_checked="1">
                                    <h1 class="article-title f-rsb fs30 hdcontent">{{ $article->title }}</h1>

                                    <div class="thread-info d-flex flex-wrap justify-content-between mb-3">
                                        <div class="d-flex detail-info-block align-items-center osahan-post-header">
                                            <div class="font-weight-600">
                                                <div class="text-truncate author-name">
                                                    Writer
                                                </div>
                                                <div class="small">
                                                    <span
                                                        class="post-date">{{ optional($article->published_at)->format('H:i d/m/Y') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="share-button d-flex align-items-center">
                                            <div class="fbplugin">
                                                <iframe
                                                    src="https://www.facebook.com/plugins/like.php?href={{ urlencode($shareUrl) }}&width=191&layout=button_count&action=like&size=small&show_faces=true&share=true&height=46&appId="
                                                    width="160" height="20" scrolling="no" frameborder="0"
                                                    allowtransparency="true" allow="encrypted-media"
                                                    style="border:none;overflow:hidden;display:block;">
                                                </iframe>
                                            </div>

                                            <a target="_blank"
                                                href="https://news.google.com/publications/CAAqBwgKMNLAqQww5MC2BA"
                                                class="ico-google-new lt">Theo dõi trên</a>
                                        </div>
                                    </div>
                                    <div class="article-content article-content_toc">
                                        {!! $article->content !!}
                                    </div>


                                    @php
                                        $shareUrl = $shareUrl ?? route('article', ['slug' => $article->slug]);
                                        $shareTitle = $article->title ?? '';

                                        $fbShareJs =
                                            "javascript: window.open('https://www.facebook.com/sharer/sharer.php?u=" .
                                            addslashes($shareUrl) .
                                            "');return false;";
                                        $twShareJs =
                                            "javascript: window.open('https://twitter.com/share?url=" .
                                            addslashes($shareUrl) .
                                            "');return false;";
                                        $pinUrl =
                                            'https://pinterest.com/pin/create/button/?' .
                                            http_build_query([
                                                'url' => $shareUrl,
                                                'description' => $shareTitle,
                                            ]);
                                    @endphp

                                </div>
                            </div>
                        </div>
                        <aside class="col col-right col-xl-3 order-xl-2 col-lg-3 order-lg-2 col-md-6 col-sm-6 col-12">
                        </aside>
                    </div>
                </div>
            </section>
            <section class="bottom-main">
                <div class="container" bis_skin_checked="1">
                    <div class="row" bis_skin_checked="1">
                        <div class="col col-12" bis_skin_checked="1"></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="promotion-sticky pc-sticky-right" bis_skin_checked="1"></div>
    </div>
@endsection
