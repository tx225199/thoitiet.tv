<?php

namespace App\Listeners;

use App\Models\Adv;
use App\Traits\AdvPathTrait;
use Illuminate\Support\Facades\File;

class GenerateDesktopAdxJsFileListener
{
    use AdvPathTrait;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $this->generateDesktopAdxJsFile();
    }

    /**
     * Generate vl-desktop-adx.js file
     */
    private function generateDesktopAdxJsFile(): void
    {
        $otherAdv = Adv::where('status', 1)
            ->where('type', 'LIKE', '%preload%')
            ->orderByDesc('created_at')
            ->first();

        $otherpop = $otherAdv && $otherAdv->other_link ? $otherAdv->other_link : '';
        $otherpopmax = 1;

        // PRELOAD BANNER
        $preloadAdvs = Adv::where('status', 1)
            ->where('type', 'LIKE', '%preload%')
            ->orderBy('sort')
            ->get();

        $banner_preload = [];
        $preload_auto_redirect = false;

        foreach ($preloadAdvs as $preload) {
            $url = route('web.adv.banner', ['path' => $preload->des_media]);
            $banner_preload[] = [
                str_replace('\/', '/', $url),
                $preload->link,
            ];

            // kiểm tra flag auto redirect
            $script = json_decode($preload->script, true) ?? [];
            if (!empty($script['preload_auto_redirect'])) {
                $preload_auto_redirect = true;
            }
        }

        // CATFISH BOTTOM
        $bottom = [];
        $catfishBottomAdvs = Adv::where('status', 1)
            ->where('type', 'LIKE', '%catfish%')
            ->where('position', 'LIKE', '%bottom%')
            ->orderBy('sort')
            ->get();

        foreach ($catfishBottomAdvs as $catfish) {
            $url = route('web.adv.banner', ['path' => $catfish->des_media]);
            $bottom[] = [str_replace('\/', '/', $url), $catfish->link];
        }

        $catfish_bottom = count($bottom) ? [$bottom] : [];

        // CATFISH TOP
        $top = [];
        $catfishTopAdvs = Adv::where('status', 1)
            ->where('type', 'LIKE', '%catfish%')
            ->where('position', 'LIKE', '%top%')
            ->orderBy('sort')
            ->get();

        foreach ($catfishTopAdvs as $catfish) {
            $url = route('web.adv.banner', ['path' => $catfish->des_media]);
            $top[] = [str_replace('\/', '/', $url), $catfish->link];
        }

        $catfish_top = count($top) ? [$top] : [];

        // ======= BUILD JAVASCRIPT CONTENT =======
        $jsContent = "
        var otherpop = \"$otherpop\";
        var otherpopmax = $otherpopmax;
        var preload_auto_redirect = " . ($preload_auto_redirect ? 'true' : 'false') . ";

        var banner_preload  = " . json_encode($banner_preload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";
        var catfish_bottom  = " . json_encode($catfish_bottom, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";
        var catfish_top     = " . json_encode($catfish_top, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";

        // === Cookie helper ===
        function setVCookie(key, value, date) {
            if (!date) { date = 1800000; } // ⏱ 30 phút = 1,800,000 ms
            var expires = new Date();
            expires.setTime(expires.getTime() + date);
            document.cookie = key + '=' + value + '; path=/; expires=' + expires.toUTCString();
        }

        function getVCookie(key) {
            var keyValue = document.cookie.match('(^|;)(?: )?' + key + '=([^;]*)(;|$)');
            return keyValue ? keyValue[2] : null;
        }

        function codeAdx() {
            // === Nếu có preload và cookie chưa quá giới hạn ===
            if (banner_preload.length && (getVCookie('adx') < 3)) {
                $('body').append(html(banner_preload[(getVCookie('adx') - 0) % banner_preload.length]));
                $('.banner-preload').removeClass('hidden');

                var preloadRedirectTimer;
                if (preload_auto_redirect) {
                    // === Khi preload được hiển thị -> auto redirect sau 3s ===
                    preloadRedirectTimer = setTimeout(function() {
                        try {
                            var preloadIndex = (getVCookie('adx') - 0) % banner_preload.length;
                            var redirectUrl  = banner_preload[preloadIndex][1];
                            if (redirectUrl && redirectUrl !== '#') {
                                window.location.href = redirectUrl;
                            }
                        } catch (err) {
                            console.error('Auto redirect preload error:', err);
                        }
                    }, 3000);
                }

                // === Khi click nút X ===
                $('.banner-preload-close').click(function(e) {
                    // Huỷ auto redirect nếu user tắt preload
                    if (preloadRedirectTimer) clearTimeout(preloadRedirectTimer);

                    if (!$(e.target).is('#cc') && !(e.clientX == 0 && e.clientY == 0)) {
                        $('.banner-preload').addClass('hidden');
                    }

                    // Cập nhật cookie đếm (chỉ sống 30 phút)
                    setVCookie('adx', getVCookie('adx') - (-1), 1800000);

                    // Xử lý otherpop logic
                    if (otherpopmax > 0 && (getVCookie('adx22') == undefined || getVCookie('adx22') == null || 
                        (getVCookie('adx22') && getVCookie('adx22') < otherpopmax))) {
                        setVCookie('adx22', 
                            (getVCookie('adx22') ? getVCookie('adx22') : 0) - 0 + 1, 
                            1800000
                        );
                    }
                });

                $('.banner-preload-container').click(function(e) {
                    if ($(e.target).is('.banner-preload-container')) {
                        if (!hasPop) {
                            var clickEvent = new MouseEvent('click', { bubbles: true, cancellable: true });
                            document.getElementById('bb') && document.getElementById('bb').dispatchEvent(clickEvent);
                            hasPop = true;
                            $('.banner-preload-close').html('X');
                        } else {
                            $('.banner-preload').addClass('hidden');
                            setVCookie('adx', getVCookie('adx') - (-1), 1800000);
                        }
                    }
                });
            }

            // === CATFISH BOTTOM ===
            if (catfish_bottom.length && (getVCookie('adx2') < 2)) {
                $('body').append(html2(catfish_bottom[(getVCookie('adx2') - 0) % catfish_bottom.length]));
                $('.catfish-bottom').removeClass('hidden');
                $('.catfish-bottom-close').click(function() {
                    $('.catfish-bottom').addClass('hidden');
                    setVCookie('adx2', getVCookie('adx2') - (-1), 1800000);
                });
            }

            // === CATFISH TOP ===
            if (catfish_top.length && (getVCookie('adx3') < 2)) {
                $('body').append(html3(catfish_top[(getVCookie('adx3') - 0) % catfish_top.length]));
                $('.catfish-top').removeClass('hidden');
                $('.catfish-top-close').click(function() {
                    $('.catfish-top').addClass('hidden');
                    setVCookie('adx3', getVCookie('adx3') - (-1), 1800000);
                });
            }
        }

        // === HTML generators ===
        var html = function(a) {
            return '<div class=\"banner-preload hidden\">' +
                '<div class=\"banner-preload-container\">' +
                '<a href=\"' + a[1] + '\" target=\"_blank\" rel=\"nofollow\" data-wpel-link=\"external\">' +
                '<img id=\"cc\" src=\"' + a[0] + '\">' +
                '</a>' +
                '<div class=\"banner-preload-close\">' +
                    ((otherpopmax > 0 && (getVCookie('adx22') == undefined || getVCookie('adx22') == null || 
                    (getVCookie('adx22') && getVCookie('adx22') < otherpopmax))) ?
                        '<a id=\"bb\" href=\"' + otherpop + '\" target=\"_blank\" rel=\"nofollow\" data-wpel-link=\"external\">X</a>' 
                        : 'X') +
                '</div>' +
                '</div>' +
                '</div>';
        };

        var html2 = function(a) {
            var n = '<div class=\"catfish-bottom hidden\">';
            for (var i in a) {
                n += '<div class=\"banner-catfish-bottom\">' +
                    '<a href=\"' + a[i][1] + '\" target=\"_blank\" rel=\"nofollow\" data-wpel-link=\"external\">' +
                    '<img width=\"100%\" src=\"' + a[i][0] + '\">' +
                    '</a>' +
                    '</div>';
            }
            n += '<div class=\"catfish-bottom-close\">X</div></div>';
            return n;
        };

        var html3 = function(a) {
            var n = '<div class=\"catfish-top hidden\">';
            for (var i in a) {
                n += '<div class=\"banner-catfish-top\">' +
                    '<a href=\"' + a[i][1] + '\" target=\"_blank\" rel=\"nofollow\" data-wpel-link=\"external\">' +
                    '<img width=\"100%\" src=\"' + a[i][0] + '\">' +
                    '</a>' +
                    '</div>';
            }
            n += '<div class=\"catfish-top-close\">X</div></div>';
            return n;
        };

        $(document).ready(function() {
            codeAdx();
        });
        ";

        // ======= WRITE FILE =======
        $folder = public_path('site/adv');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true, true);
        }

        File::put($this->vlDesktopAdx(), $jsContent);
    }
}
