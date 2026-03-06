@extends('site.master')

@section('head')
    {!! $headMeta !!}

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
@endsection

@section('main')
    <div class="section-content" bis_skin_checked="1">
        <div class="promotion-sticky pc-sticky-left" bis_skin_checked="1">
        </div>
        <div class="container" bis_skin_checked="1">
            {!! $breadcrumbHtml !!}
        </div>

        <div class="container my-3 sticky-top" bis_skin_checked="1">
            <nav class="navbar-dark bg-weather-primary menu-location">
                <div class="nav-scroller" bis_skin_checked="1">

                    {!! $tabsNavHtml !!}

                </div>
            </nav>
        </div>
        <div class="container main-location-heading" bis_skin_checked="1">
            {!! $weatherDetail !!}
        </div>
        <div class="promotion-sticky pc-sticky-right" bis_skin_checked="1">
        </div>
    </div>
@endsection


@section('script')
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <script defer="" src="/themes/tinhte/public/js/app_config.js" type="text/javascript"></script>

    <script defer="" src="/themes/tinhte/public/js/rains_hourly.js" type="text/javascript"></script>
@endsection



