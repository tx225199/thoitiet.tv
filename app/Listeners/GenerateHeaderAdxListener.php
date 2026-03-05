<?php

namespace App\Listeners;

use App\Models\Adv;
use App\Traits\AdvPathTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GenerateHeaderAdxListener
{
    use AdvPathTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $this->generateHeaderAdx();
    }


    /**
     * Undocumented function
     * vl-header-adx.js
     * @return void
     */
    private function generateHeaderAdx()
    {
        $advs = Adv::where('status', 1)
            ->where('type', 'LIKE', '%banner%')
            ->where('position', 'LIKE', '%top%')
            ->orderBy('sort', 'asc')
            ->get();

        Log::error('RUN HERE', ['adv' => count($advs)]);

        $adsSmallScreen = '';
        $adsLargeScreen = '';

        foreach ($advs as $ad) {

            if (!empty($ad->mob_media)) {
                $mobPath = route('web.adv.banner', ['path' => $ad->mob_media]);
                $adHtml = "<p><a href=\"{$ad->link}\" target=\"_blank\" rel=\"nofollow\"><img src=\"{$mobPath}\" width=\"300px\"></a></p>";
                $adsSmallScreen .= $adHtml;
            }

            if (!empty($ad->des_media)) {
                $deskPath = route('web.adv.banner', ['path' => $ad->des_media]);
                $adHtml = "<p style=\"max-width: 728px; margin: 3px auto;\"><a href=\"{$ad->link}\" target=\"_blank\" rel=\"nofollow\"><img src=\"{$deskPath}\" width=\"100%\"></a></p>";
                $adsLargeScreen .= $adHtml;
            }
        }

        $jsContent = "
        var bannerAdv = function() {
            (function() {
                var x = document.getElementById('section-brand');
                x.style.margin = '0 5px';
                x.style.textAlign = 'center';

                var htmlSmallScreen = `
                    $adsSmallScreen
                `;

                var htmlLargeScreen = `
                    $adsLargeScreen
                `;

                x.innerHTML = window.innerWidth < 300 ? '' : window.innerWidth < 768 ? htmlSmallScreen : htmlLargeScreen;
            })();
        }

        window.onload = function() {
            bannerAdv();
        };
        ";

        $folder = public_path('site/adv');

        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true, true);
        }

        File::put($this->vlHeaderAdx(), $jsContent);
    }
}
