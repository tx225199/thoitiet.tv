<aside class="aside-160">
    {!! $aside160 ?? '' !!}

    @if (!empty($smSidebarRightBanners) && count($smSidebarRightBanners))
        <div class="aside-160-aside">
            @foreach ($smSidebarRightBanners as $banner)
                @php
                    $mediaPath = $banner->des_media ?: $banner->mob_media;
                    $imgUrl = $mediaPath ? route('web.adv.banner', ['path' => $mediaPath]) : null;
                @endphp

                @if ($imgUrl)
                    <a href="{{ $banner->link }}" target="_blank" rel="noopener">
                        <img src="{{ $imgUrl }}" alt="{{ $banner->title ?? 'Quảng cáo nhỏ' }}">
                    </a>
                @endif
            @endforeach
        </div>
    @endif

</aside>

<aside class="aside-300">
    {!! $aside300 ?? '' !!}

    @if (!empty($sidebarRightBanners) && count($sidebarRightBanners))
        <div class="aside-300-aside">
            @foreach ($sidebarRightBanners as $banner)
                @php
                    $mediaPath = $banner->des_media ?: $banner->mob_media;
                    $imgUrl = $mediaPath ? route('web.adv.banner', ['path' => $mediaPath]) : null;
                @endphp

                @if ($imgUrl)
                    <a href="{{ $banner->link }}" target="_blank" rel="noopener">
                        <img src="{{ $imgUrl }}" alt="{{ $banner->title ?? 'Quảng cáo' }}">
                    </a>
                @endif
            @endforeach
        </div>
    @endif

</aside>
