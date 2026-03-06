<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdvController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ProxyController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\AjaxSearchController;
use App\Http\Controllers\ClientLocationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PathProxyController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/sitemap.xml', function () {
    return response()->file(storage_path('app/public/sitemaps/sitemap.xml'), [
        'Content-Type' => 'application/xml'
    ]);
});

Route::middleware(['admin'])
    ->name('admin.')
    ->prefix('admin')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');

        // account
        Route::get('/accounts', [AdminController::class, 'accounts'])->name('account.index');
        Route::post('/account/store', [AdminController::class, 'store'])->name('account.store');
        Route::get('/upgrading', [AdminController::class, 'upgrading'])->name('upgrading');


        // genres
        Route::prefix('genres')->name('genres.')->group(function () {
            Route::get('/', [GenreController::class, 'index'])->name('index');
            Route::get('/create', [GenreController::class, 'create'])->name('create');
            Route::get('/edit/{id}', [GenreController::class, 'edit'])->name('edit');

            Route::put('/update/{id}', [GenreController::class, 'update'])->name('update');
            Route::post('/store', [GenreController::class, 'store'])->name('store');
        });

        // proxies
        Route::prefix('proxies')->name('proxies.')->group(function () {
            Route::get('/', [ProxyController::class, 'index'])->name('index');
            Route::get('/create', [ProxyController::class, 'create'])->name('create');
            Route::get('/edit/{id}', [ProxyController::class, 'edit'])->name('edit');

            Route::put('/update/{id}', [ProxyController::class, 'update'])->name('update');
            Route::post('/store', [ProxyController::class, 'store'])->name('store');
        });


        // news
        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [ArticleController::class, 'index'])->name('index');
            Route::get('/scheduled', [ArticleController::class, 'scheduled'])->name('scheduled');
            Route::get('/hidden', [ArticleController::class, 'hidden'])->name('hidden');

            Route::get('/create', [ArticleController::class, 'create'])->name('create');
            Route::get('/edit/{id}', [ArticleController::class, 'edit'])->name('edit');

            Route::put('/update/{id}', [ArticleController::class, 'update'])->name('update');
            Route::post('/store', [ArticleController::class, 'store'])->name('store');

            Route::delete('/destroy/{id}', [ArticleController::class, 'destroy'])->name('destroy');
            Route::post('/active', [ArticleController::class, 'active'])->name('active');
            Route::post('/highlight', [ArticleController::class, 'highlight'])->name('highlight');
        });

        // routes/web.php
        Route::post('/tinymce/upload', [UploadController::class, 'tinymce'])->name('tinymce.upload');

        // setting
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/store', [SettingController::class, 'store'])->name('settings.store');

        Route::get('settings/custom-css', [SettingController::class, 'editCustomCss'])->name('settings.css.edit');
        Route::post('settings/custom-css', [SettingController::class, 'updateCustomCss'])->name('settings.css.update');

        //adv
        Route::get('/advs', [AdvController::class, 'index'])->name('adv.index');

        Route::prefix('adv')->name('adv.')->group(function () {

            Route::post('/store', [AdvController::class, 'store'])->name('store');
            Route::get('/banner', [AdvController::class, 'banner'])->name('banner');
            Route::get('/banner-script', [AdvController::class, 'bannerScript'])->name('banner-script');
            Route::get('/catfish', [AdvController::class, 'catfish'])->name('catfish');
            Route::get('/preload', [AdvController::class, 'preload'])->name('preload');
            Route::get('/pushjs', [AdvController::class, 'pushjs'])->name('pushjs');
            Route::get('/popupjs', [AdvController::class, 'popupjs'])->name('popupjs');
            Route::get('/text-link', [AdvController::class, 'textLink'])->name('text-link');
            Route::get('/header', [AdvController::class, 'header'])->name('header');
            Route::get('/bottom', [AdvController::class, 'bottom'])->name('bottom');
            Route::post('/refresh', [AdvController::class, 'refresh'])->name('refresh');
            Route::post('/active', [AdvController::class, 'active'])->name('active');
            Route::post('/delete/{id}', [AdvController::class, 'delete'])->name('delete');
            Route::get('/popunder', [AdvController::class, 'popunder'])->name('popunder');
            Route::post('/popunder', [AdvController::class, 'storePopunder'])->name('popunder.store');
        });

        Route::get('/pages/term',  [AdminPageController::class, 'editTerm'])->name('pages.term.edit');
        Route::post('/pages/term', [AdminPageController::class, 'upsertTerm'])->name('pages.term.upsert');

        Route::get('/pages/contact',  [AdminPageController::class, 'editContact'])->name('pages.contact.edit');
        Route::post('/pages/contact', [AdminPageController::class, 'upsertContact'])->name('pages.contact.upsert');


        Route::get('/artisan', function () {
            return view('admin.setting.artisan-runner');
        });

        Route::post('/artisan', function (\Illuminate\Http\Request $request) {
            $phpPath = '/usr/bin/php';
            $command = $phpPath . ' artisan ' . escapeshellcmd($request->input('command'));

            $process = Symfony\Component\Process\Process::fromShellCommandline($command, base_path());
            $process->run();

            return response()->json([
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ]);
        });

        Route::get('/refresh-storage-link', function () {
            $storageLink = public_path('storage');

            // Xoá symbolic link hoặc thư mục thật
            if (is_link($storageLink)) {
                unlink($storageLink);
            } elseif (file_exists($storageLink)) {
                File::deleteDirectory($storageLink);
            }

            // Tạo lại symbolic link
            $phpBinary = '/usr/bin/php';
            $command = [$phpBinary, 'artisan', 'storage:link'];

            $process = new Process($command, base_path());
            $process->run();

            return response()->json([
                'success' => $process->isSuccessful(),
                'output'  => $process->getOutput(),
                'error'   => $process->getErrorOutput(),
            ]);
        });

        Route::prefix('brands')->name('brand.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/store', [BrandController::class, 'store'])->name('store');
            Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
        });
    });

Route::get('/api/account/detail/{id}', [AdminController::class, 'detail'])->name('detail');

Route::group(['prefix' => 'filemanager', 'middleware' => ['web', 'admin']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});

// ads
Route::get('/storage/uploads/advs/{path?}', function ($path) {
    $cacheKey = 'adv_' . $path;

    if (Cache::store('file')->has($cacheKey)) {
        $imageString = Cache::store('file')->get($cacheKey);
    } else {
        $imagePath = storage_path('app/public/uploads/advs/' . $path);

        if (!file_exists($imagePath)) {
            $imagePath = public_path('system/img/no-image.png');
        }

        $imageString = file_get_contents($imagePath);

        Cache::store('file')->put($cacheKey, $imageString, now()->addMinutes(60));
    }

    $response = response($imageString)->header('Content-Type', 'image/gif');
    $response->header('Cache-Control', 'public, max-age=31536000');
    return $response;
})->name('web.adv.banner');

Route::get('/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/login',  [AdminController::class, 'postLogin'])->name('admin.post.login');
Route::post('/logout',  [AdminController::class, 'logout'])->name('admin.logout');


Route::get('/{slug}.html', [PageController::class, 'genre'])->name('genre');
Route::get('/tin-tuc/{slug}.html', [PageController::class, 'article'])->name('article');

// weather

Route::get('/{citySlug}', [WeatherController::class, 'show'])
    ->where('citySlug', '^(?!admin|api|ajax|asset|weather|themes|uploads|storage|login|logout).+')
    ->name('city.show');

Route::get('/{citySlug}/{pageSlug}', [WeatherController::class, 'show'])
    ->where([
        'citySlug' => '^(?!admin|api|ajax|asset|weather|themes|uploads|storage|login|logout).+',
        'pageSlug' => 'theo-gio|ngay-mai|3-ngay-toi|5-ngay-toi|7-ngay-toi|10-ngay-toi|15-ngay-toi|30-ngay-toi',
    ]);

// =============================
// FRONTEND MIRROR & ASSET PROXY
// =============================

Route::post('/home-client-location', [ClientLocationController::class, 'store'])->name('home.client-location');

Route::get('/ajax/search', [AjaxSearchController::class, 'search'])->name('ajax.search');

// weatherapi => /weather/...  (giữ nguyên path sau domain)
Route::get('/weather/{path}', [PathProxyController::class, 'weatherapi'])
    ->where('path', '.*');

// thoitiet assets (giữ nguyên path)
Route::get('/themes/{path}', [PathProxyController::class, 'thoitietThemes'])->where('path', '.*');
Route::get('/css/{path}',    [PathProxyController::class, 'thoitietCss'])->where('path', '.*');
Route::get('/js/{path}',     [PathProxyController::class, 'thoitietJs'])->where('path', '.*');
Route::get('/images/{path}', [PathProxyController::class, 'thoitietImages'])->where('path', '.*');
Route::get('/img/{path}',    [PathProxyController::class, 'thoitietImg'])->where('path', '.*');
Route::get('/fonts/{path}',  [PathProxyController::class, 'thoitietFonts'])->where('path', '.*');
Route::get('/assets/{path}', [PathProxyController::class, 'thoitietAssets'])->where('path', '.*');

// cuối cùng mới tới page mirror
Route::get('/{any?}', [HomeController::class, 'index'])->where('any', '.*');

