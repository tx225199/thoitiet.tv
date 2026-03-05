<?php

namespace App\Listeners;

use App\Models\Adv;
use App\Traits\AdvPathTrait;
use Illuminate\Support\Facades\File;

class GenerateUnderPlayerAdxListener
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
        $this->generateUnderPlayerAdx();
    }

    /**
     * Undocumented function
     * vl-underplayer-adx.js
     * @return void
     */
    private function generateUnderPlayerAdx()
    {
        $advs = Adv::where('status', 1)
            ->where('type', 'LIKE', '%banner%')
            ->where('position', 'LIKE', '%center%')
            ->orderBy('sort', 'asc')
            ->get();

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
        var underPlayer = function() {
            (function() {
                var x = document.getElementById('vl-underplayer-adx');
                x.style.margin = '0 5px';
                x.style.textAlign = 'center';

                var htmlSmallScreen = `
                    $adsSmallScreen
                `;

                var htmlLargeScreen = `
                    $adsLargeScreen
                `;

                if (window.innerWidth < 300) {
                    x.innerHTML = '';
                } else {
                    x.innerHTML = window.innerWidth < 768 ? htmlSmallScreen : htmlLargeScreen;
                }
            })();
        }

        // Initialize the underPlayer function when the document is ready
        $(document).ready(function() {
            underPlayer();
        });
        ";

        $folder = public_path('site/adv');

        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true, true);
        }

        File::put($this->vlUnderAdx(), $jsContent);
    }
}
