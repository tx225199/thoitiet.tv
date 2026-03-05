<?php

namespace App\Providers;

use App\Models\Adv;
use App\Models\Brand;
use Illuminate\Support\ServiceProvider;
use App\Models\Genre;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Jenssegers\Agent\Agent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
           
            if (Schema::hasTable('settings')) {
                
                $menuAdmins = $this->readFiles();
                View::share('menuAdmins', $menuAdmins);

                $settings = Setting::all();

                $arrSettings = array();
                foreach ($settings as $item) {
                    $arrSettings[$item->key] = $item->value;
                }

                $version   = $arrSettings['version'] ?? 1.0;
                $agent  = new Agent();
                $isDesktop = $agent->isDesktop() ? true : false;

                $textLinks = Adv::where(['status' => 1])->where('type', 'LIKE', '%textlink%')->orderBy('sort', 'desc')->get();
                $pushJs = Adv::where(['status' => 1])->where('type', 'LIKE', '%push-js%')->orderBy('sort', 'desc')->get();

                $popupJs = Adv::where(['status' => 1])->where('type', 'LIKE', '%popup-js%')->orderBy('sort', 'desc')->get();
                $headerScript = Adv::where(['status' => 1])->where('type', 'LIKE', '%header%')->orderBy('sort', 'desc')->get();

                $bottomScript = Adv::where(['status' => 1])->where('type', 'LIKE', '%bottom%')->orderBy('sort', 'desc')->get();

                $bannerTopScript = Adv::where(['status' => 1])->where('type', 'LIKE', '%banner-script%')->where('position', 'LIKE', '%top%')->orderBy('sort', 'desc')->get();
                $bannerCenterScript = Adv::where(['status' => 1])->where('type', 'LIKE', '%banner-script%')->where('position', 'LIKE', '%center%')->orderBy('sort', 'desc')->get();

                $sidebarLeftBanners = Adv::where(['status' => 1])->where('type', 'LIKE', 'banner')->where('position', 'LIKE', '%sidebar_left%')->orderBy('sort', 'desc')->get();

                $banners = Adv::where(['status' => 1])->where('type', 'LIKE', 'banner')->where('position', 'LIKE', '%sidebar_right')->orderBy('sort', 'desc')->get();

                $sidebarRightBanners = Adv::where(['status' => 1])->where('type', 'LIKE', 'banner')->where('position', 'LIKE', '%sidebar_right')->orderBy('sort', 'desc')->get();

                $smSidebarRightBanners = Adv::where(['status' => 1])->where('type', 'LIKE', 'banner')->where('position', 'LIKE', '%sidebar_right_sm%')->orderBy('created_at', 'desc')->get();
                $fixedBanner = Adv::where(['status' => 1])->where('type', 'LIKE', 'banner')->where('position', 'LIKE', '%fixed%')->orderBy('created_at', 'desc')->first();

                $popunder = Adv::where(['status' => 1])->where('type', 'LIKE', '%popunder%')->orderBy('sort', 'desc')->get();

                $brands = Brand::where(['status' => 1])->orderBy('sort', 'desc')->get();

                $genres = Genre::where(['hidden' => 0])
                    ->where('slug', '!=', '')
                    ->orderBy('sort', 'asc')
                    ->get();

                View::share('genres', $genres);

                View::share('settings', $arrSettings);
                View::share('version', $version);
                View::share('isDesktop', $isDesktop);

                View::share('textLinks', $textLinks);

                View::share('pushJs', $pushJs);
                View::share('popupJs', $popupJs);

                View::share('headerScript', $headerScript);
                View::share('bottomScript', $bottomScript);

                View::share('bannerTopScript', $bannerTopScript);
                View::share('bannerCenterScript', $bannerCenterScript);

                View::share('popunder', $popunder);

                View::share('sidebarLeftBanners', $sidebarLeftBanners);
                View::share('sidebarRightBanners', $sidebarRightBanners);

                View::share('smSidebarRightBanners', $smSidebarRightBanners);

                View::share('fixedBanner', $fixedBanner);

                View::share('brands', $brands);

                View::share('banners', $banners);

            }
        } catch (Exception $e) {
            Log::error('Errr', ['err' => $e]);
        }
    }

    public function readFiles()
    {
        $file = base_path('resources/views/admin/menus.json');
        $jsonString = file_get_contents($file);
        $data = json_decode($jsonString, true);
        return $data;
    }
}
