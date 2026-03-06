<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png"
        href="/uploads/images/setting/Mazart/2024/12/26/thoitiettv-1735211602.png?ver=1754472522">

    @yield('head')

    <link rel="stylesheet" href="{{ url('assets/css/style.css') }}">

    <link href="{{ url('/') }}/themes/tinhte/public/css/app_style.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="{{ url('assets/css/style02.css') }}">

    <script>
        var WN_Data = {
            app_url: "https://thoitiet.tv",
            prefix_url: "",
            full_url: "https://thoitiet.tv",
            locale: "vi",
            user_id: null,
            user: null,
            session_id: "CbNaqgkooXtJCRnAuM6VcSCWqXy7J910kGCUjFll"
        };
    </script>
    <script>
        const loadScriptsTimer = setTimeout(loadScripts, 3000);
        const userInteractionEvents = ["scroll", "mousemove", "mouseover", "keydown", "touchmove", "touchstart"];
        userInteractionEvents.forEach(function(event) {
            window.addEventListener(event, triggerScriptLoader, {
                passive: true
            });
        });

        function triggerScriptLoader() {
            loadScripts();
            clearTimeout(loadScriptsTimer);
            userInteractionEvents.forEach(function(event) {
                window.removeEventListener(event, triggerScriptLoader, {
                    passive: true
                });
            });
        }

        function loadScripts() {
            console.log('lazy script loaded');
            document.querySelectorAll("script[data-type='lazy']").forEach(function(elem) {
                elem.setAttribute("src", elem.getAttribute("data-src"));
            });
            document.querySelectorAll("iframe[data-type='lazy']").forEach(function(elem) {
                elem.setAttribute("src", elem.getAttribute("data-src"));
            });
        }
    </script>
</head>

<body class="home  layout_wide">
    <div id="app">
        <div class="no-margin-ads">
        </div>

        {{-- header --}}
        @include('site.widgets.header')
        {{-- end header --}}

        {{-- main --}}
        @yield('main')
        {{-- end main --}}

        {{-- footer --}}
        @include('site.widgets.footer')
        {{-- end footer --}}

        <div class="clearfix"></div>
        <div class="woodmart-close-side"></div>

    </div>

    {{-- script --}}
    @include('site.widgets.script')
    {{-- end script --}}


</body>

</html>
