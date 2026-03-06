@extends('site.master')

@section('head')
    @php
        $siteTitle = $settings['title'] ?? 'Dự báo thời tiết 63 tỉnh và thành phố chính xác nhất Việt Nam';
        $desc =
            $settings['description'] ??
            'Website cập nhật tình hình dự báo thời tiết từng ngày, từng giờ. Diễn biến thời tiết các tỉnh thành, quận huyện ở Việt Nam. Website: thoitiet.tv';
        $keywords =
            $settings['meta_keywords'] ??
            'Dự báo thời tiết, thời tiết hôm nay, thời tiết ngày mai, thời tiết 3 ngày tới, thời tiết 7 ngày tới';

        // canonical home
        $canonical = url('/');

        $ogImage = isset($settings['logo']) && $settings['logo'] != '' ? sourceSetting($settings['logo']) : '/images/logo.svg';

        // json-ld images: giữ giống mẫu (1 absolute + 1 relative)
        $jsonLdImages = [
            $ogImage,
            $settings['og_image_2'] ?? '/uploads/images/setting/Mazart/2024/08/14/thoitietvn-1000x500-3-1723606827.png',
        ];
    @endphp

    <title>{{ $siteTitle }}</title>

    <meta name="description" content="{{ $desc }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="robots" content="{{ $settings['robots'] ?? 'index,follow' }}">
    <meta name="google-site-verification" content="7pTgpatVr03nepCHbCb1GsiRKL8QQgO0cm78IWB74R8">
    <meta name="author" content="{{ $settings['author'] ?? 'thoitiet.tv' }}">

    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="vi-vn" href="{{ $canonical }}">

    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:locale:alternate" content="vi_VN">
    <meta property="og:title" content="{{ $siteTitle }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:height" content="315">
    <meta property="og:image:width" content="600">

    <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $siteTitle,
    'description' => $desc,
    'url' => $canonical,
    'image' => $jsonLdImages,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection


@section('main')
    <div class="container">
        <div class="no-margin-ads">
        </div>
    </div>

    <div class="section-content">
        <div class="promotion-sticky pc-sticky-left">
        </div>

        <div class="wraper-content home-page" id="home_page">


            {!! $boxCurrentWeather !!}

            <div class="container">
                <div class="row">
                    <div class="col-12 col-md-8">
                        {!! $boxFeaturedWeather !!}
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="pd-h bs-h">
                            <h2 class="font-h2">Bản đồ Windy</h2>
                            @php
                                $lat = $windyLat ?? 21.033;
                                $lng = $windyLng ?? 105.833;

                                $windyUrl =
                                    'https://embed.windy.com/embed2.html?' .
                                    http_build_query([
                                        'lat' => $lat,
                                        'lon' => $lng,
                                        'detailLat' => $lat,
                                        'detailLon' => $lng,
                                        'width' => '100%',
                                        'height' => 450,
                                        'zoom' => 7,
                                        'level' => 'surface',
                                        'overlay' => 'wind',
                                        'product' => 'ecmwf',
                                        'menu' => '',
                                        'message' => 'true',
                                        'marker' => 'true',
                                        'calendar' => 'now',
                                        'pressure' => 'true',
                                        'type' => 'map',
                                        'location' => 'coordinates',
                                        'detail' => '',
                                        'metricWind' => 'default',
                                        'metricTemp' => '°C',
                                        'radarRange' => -1,
                                    ]);
                            @endphp

                            <div>
                                <iframe width="100%" height="350" src="{{ $windyUrl }}" frameborder="0"></iframe>
                            </div>
                        </div>

                        <div class="current-location top-news bs-h">
                            <div id="calendar" style="width: 385.328px;">
                                <div id="calendar_header" style="background-color: rgb(192, 57, 43); height: 55.0469px;"><i
                                        class="icon-chevron-left" style="line-height: 55.0469px;"><svg
                                            xmlns="http://www.w3.org/2000/svg" height="16" width="10"
                                            viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome  - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                            <path
                                                d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z">
                                            </path>
                                        </svg></i>
                                    <h3>{{ strtoupper(now()->format('F Y')) }}</h3>
                                    <i class="icon-chevron-right" style="line-height: 55.0469px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="16" width="10"
                                            viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome  - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                            <path
                                                d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z">
                                            </path>
                                        </svg>
                                    </i>
                                </div>
                                <div id="calendar_weekdays"></div>
                                <div id="calendar_content"></div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- THOI TIET 63 TINH THANH --}}
                @include('site.widgets.weather')

            </div>
            <div class="d-none">
            </div>

            {{-- TIN TUC --}}

            <div class="home-list-post" style="background-image: url(/themes/tinhte/public/images/bg_home.jpg)">
                <div class="container">
                    <h2>Tin tức nổi bật</h2>

                    <div class="row">
                        @foreach ($hotArticles ?? collect() as $a)
                            @php
                                $href = route('article', ['slug' => $a->slug]);
                                $img = asset_media($a->avatar);
                            @endphp

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="list-top-new-items">
                                    <div class="news-thumbnail">
                                        <a rel="nofollow" href="{{ $href }}" class="top-news-link">
                                            @if ($img)
                                                <img src="{{ $img }}" class="me-3"
                                                    alt="{{ $a->title }}">
                                            @endif
                                        </a>
                                    </div>

                                    <div class="news-body">
                                        <h5 class="mt-0 mb-1">
                                            <a rel="nofollow" href="{{ $href }}">{{ $a->title }}</a>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="background-color: #f6f5f0; display:none;"></div>
            </div>

            {{-- END TIN TUC --}}

        </div>
        <div class="promotion-sticky pc-sticky-right">
        </div>
    </div>
    <!--partner.blade.php-->
    <div class="container">
    </div>
@endsection
