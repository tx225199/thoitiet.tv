<?php

use App\Models\Proxy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpClient\HttpClient;

if (!function_exists('makeSlug')) {
    function makeSlug($string)
    {
        $search = array(
            '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
            '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
            '#(ì|í|ị|ỉ|ĩ)#',
            '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
            '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
            '#(ỳ|ý|ỵ|ỷ|ỹ)#',
            '#(đ)#',
            '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#',
            '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
            '#(Ì|Í|Ị|Ỉ|Ĩ)#',
            '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
            '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#',
            '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#',
            '#(Đ)#',
            "/[^a-zA-Z0-9\-\_]/",
        );
        $replace = array(
            'a',
            'e',
            'i',
            'o',
            'u',
            'y',
            'd',
            'A',
            'E',
            'I',
            'O',
            'U',
            'Y',
            'D',
            '-',
        );
        $string = preg_replace($search, $replace, $string);
        $string = preg_replace('/(-)+/', '-', $string);
        $string = strtolower($string);
        return $string;
    }
}

if (!function_exists('downloadImage')) {
    function downloadImage(string $url, string $filename, bool $isThumb = false, bool $isActor = false): ?string
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withOptions([
                'verify'  => false,
                'timeout' => 20,
            ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122 Safari/537.36',
                    'Accept'     => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    // Referer có thể null -> fallback root host
                    'Referer'    => ($h = parse_url($url, PHP_URL_HOST)) ? ('https://' . $h . '/') : '',
                ])
                ->get($url);

            if (!$resp->successful()) {
                Log::error('Download image failed (http)', ['url' => $url, 'status' => $resp->status()]);
                return null;
            }

            // KHÔNG return sớm theo Content-Type nữa (Telegram có thể trả octet-stream)
            $contentType = strtolower((string) $resp->header('Content-Type'));

            // Optional: giới hạn size ~10MB (nếu không có header, vẫn cho qua)
            $len = (int)($resp->header('Content-Length') ?? 0);
            if ($len > 0 && $len > 10 * 1024 * 1024) {
                Log::warning('Skip too-large image', ['url' => $url, 'bytes' => $len]);
                return null;
            }

            $bin = $resp->body();

            // Kiểm tra thực sự là ảnh
            $info = @getimagesizefromstring($bin);
            if (!$info) {
                Log::error('Download image failed (getimagesizefromstring)', [
                    'url' => $url,
                    'content_type' => $contentType,
                ]);
                return null;
            }

            // Tạo GD image
            $im = @imagecreatefromstring($bin);
            if (!$im) {
                Log::error('Download image failed (imagecreatefromstring)', ['url' => $url]);
                return null;
            }

            // Đuôi .webp
            if (!str_ends_with(strtolower($filename), '.webp')) {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            }

            $folder       = $isActor ? 'actors' : ($isThumb ? 'thumbs' : 'posters');
            $publicBase   = "images/{$folder}";
            $storageFolder = storage_path("app/public/{$publicBase}");

            if (!is_dir($storageFolder)) {
                @mkdir($storageFolder, 0777, true);
                @chmod($storageFolder, 0777);
            }

            $savePath = "{$storageFolder}/{$filename}";

            if (function_exists('imagepalettetotruecolor')) imagepalettetotruecolor($im);
            imagealphablending($im, true);
            imagesavealpha($im, true);

            // Nếu GD không hỗ trợ WEBP, fallback sang PNG
            if (function_exists('imagewebp')) {
                imagewebp($im, $savePath, 95);
            } else {
                $savePath = preg_replace('/\.webp$/i', '.png', $savePath);
                imagepng($im, $savePath, 6);
                $filename = preg_replace('/\.webp$/i', '.png', $filename);
            }
            imagedestroy($im);

            return "{$publicBase}/{$filename}";
        } catch (\Throwable $e) {
            Log::error('Download image exception', ['url' => $url, 'err' => $e->getMessage()]);
            return null;
        }
    }
}


if (!function_exists('uploadFileAdv')) {
    function uploadFileAdv($file, $name, $folder = 'uploads')
    {
        // Get the original file extension
        $extension = $file->getClientOriginalExtension();

        // Generate a unique name for the file
        $filename = $name . '-' . uniqid() . '.' . $extension;

        // Define full folder path (storage/app/public/...)
        $folderPath = storage_path('app/public/' . $folder);

        // Ensure the directory exists and has correct permissions
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        @chmod($folderPath, 0777);

        // Save the file
        $file->storeAs('public/' . $folder, $filename);

        return $filename;
    }
}

if (!function_exists('uploadForSetting')) {
    function uploadForSetting($file, $image, $name)
    {
        $folderDir = 'public/uploads/logo/';
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $thumbName = $name . '-' . time() . '.' . $ext;

        $fullPath = storage_path('app/' . $folderDir);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        @chmod($fullPath, 0777);

        // Nếu là SVG → copy file gốc, không dùng Intervention
        if (strtolower($file->getClientMimeType()) === 'image/svg+xml') {
            Storage::put($folderDir . $thumbName, file_get_contents($file));
        } else {
            $image = \Intervention\Image\Facades\Image::make($file);
            $imageStream = $image->stream('png');
            Storage::put($folderDir . $thumbName, $imageStream->__toString());
        }

        return $thumbName;
    }
}

if (!function_exists('sourceSetting')) {
    function sourceSetting($image)
    {
        return url('storage/uploads/logo/' . $image);
    }
}


if (!function_exists('uploadImageLocal')) {
    /**
     * Upload ảnh local và convert sang .webp
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $name   Tên base (không đuôi), ví dụ: 'tien-dien-...-avatar'
     * @param bool   $isThumb true => lưu vào images/thumbs, false => images/posters
     * @return string|null    Relative path: "images/thumbs/xxx.webp" | null nếu lỗi
     */
    function uploadImageLocal($file, string $name, bool $isThumb = false): ?string
    {
        try {
            $folder        = $isThumb ? 'thumbs' : 'posters';
            $storageFolder = "public/images/{$folder}";
            $publicPrefix  = "images/{$folder}";

            // chuẩn hoá tên file
            $base     = Str::slug($name);
            $filename = $base . '-' . time() . '.webp';

            // đảm bảo thư mục tồn tại
            Storage::makeDirectory($storageFolder);

            // convert -> webp
            $image = Image::make($file)->encode('webp', 80);
            Storage::put("{$storageFolder}/{$filename}", $image->__toString());

            // TRẢ VỀ RELATIVE PATH đúng chuẩn hiển thị: images/thumbs/xxx.webp
            return "{$publicPrefix}/{$filename}";
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('generateRandomCode')) {
    function generateRandomCode(int $length = 8): string
    {
        return strtolower(substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 5)), 0, $length));
    }
}

if (!function_exists('getRandomProxy')) {
    /**
     * Lấy proxy nhanh từ database.
     * - $rotateBefore = true: xoay IP proxy vừa dùng trước (nếu có) rồi trả lại.
     * - $rotateBefore = false (mặc định): chỉ chọn proxy mới (round-robin), không xoay gì cả.
     *
     * Trả về:
     * - Chuỗi proxy dạng http://user:pass@ip:port nếu có
     * - NULL nếu không có proxy khả dụng (không sử dụng proxy)
     */
    function getRandomProxy(bool $rotateBefore = false): ?string
    {
        // ====== Lấy danh sách proxy đang active từ DB ======
        static $proxies = null;
        if ($proxies === null) {
            $proxies = Proxy::where('active', true)->orderBy('id')->get();
        }

        if ($proxies->isEmpty()) {
            return null; // không có proxy nào => không dùng proxy
        }

        // ====== State trong 1 request ======
        static $idx = 0;
        static $lastProxy = null;
        static $lastRotateAt = [];

        // ====== Nếu yêu cầu rotate proxy vừa dùng ======
        if ($rotateBefore && $lastProxy && $lastProxy->rotate_url) {
            $now = time();
            $last = $lastRotateAt[$lastProxy->id] ?? 0;
            $cooldown = $lastProxy->rotate_cooldown ?? 60;

            if (($now - $last) >= $cooldown) {
                try {
                    Http::timeout(6)->get($lastProxy->rotate_url);
                    $lastRotateAt[$lastProxy->id] = $now;
                    $lastProxy->update(['last_rotated_at' => now()]);
                    usleep(900_000); // chờ IP mới áp dụng
                } catch (\Throwable $e) {
                    // ignore lỗi xoay IP
                }
            }

            return buildProxyUrl($lastProxy);
        }

        // ====== Chọn proxy mới theo round-robin ======
        $proxy = $proxies[$idx % $proxies->count()];
        $idx++;

        $lastProxy = $proxy;
        $proxy->update(['last_used_at' => now()]);

        return buildProxyUrl($proxy);
    }

    /**
     * Convert proxy model thành URL dạng http://user:pass@ip:port
     */
    function buildProxyUrl(\App\Models\Proxy $proxy): string
    {
        [$ip, $port] = explode(':', $proxy->ip);
        $user = $proxy->username;
        $pass = $proxy->password;

        return ($user && $pass)
            ? "http://{$user}:{$pass}@{$ip}:{$port}"
            : "http://{$ip}:{$port}";
    }
}

if (!function_exists('rotateProxyIpByIp')) {
    function rotateProxyIpByIp(string $ip): void
    {
        $map = [
            '103.183.119.19' => [
                'https://api.zingproxy.com/getip/vn/f7dc257dd88962cf267eb746f10d7809f24f7c77',
                'https://api.zingproxy.com/getip/vn/6b00a72c66bab75fc8c34cfe4ddd6b4e1407c7bf',
            ],
        ];

        if (!isset($map[$ip])) {
            Log::info("Không tìm thấy link đổi IP cho proxy $ip");
            return;
        }

        foreach ($map[$ip] as $changeIpUrl) {
            try {
                Http::timeout(10)->get($changeIpUrl);
                Log::info("Đã gọi đổi IP thành công cho proxy $ip - $changeIpUrl");
                sleep(1); // đợi IP xoay
            } catch (\Throwable $e) {
                Log::warning("Gọi API đổi IP thất bại cho $ip", [
                    'url'   => $changeIpUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}


if (! function_exists('asset_media')) {
    function asset_media(?string $path): string
    {
        if (!$path) return '';
        return preg_match('/^(https?:)?\/\//', $path)
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('removeIframeSandbox')) {
    /**
     * Xoá thuộc tính sandbox trong iframe
     */
    function removeIframeSandbox(?string $html): ?string
    {
        if (!$html) return $html;
        return preg_replace('/\s+sandbox="[^"]*"/i', '', $html);
    }
}

if (!function_exists('today_vietnamese')) {
    /**
     * Trả về chuỗi ngày hiện tại theo định dạng:
     * "Hôm nay: Thứ Năm ngày 09/10/2025"
     */
    function today_vietnamese(): string
    {
        // Map thứ sang tiếng Việt
        $days = [
            'Monday'    => 'Thứ Hai',
            'Tuesday'   => 'Thứ Ba',
            'Wednesday' => 'Thứ Tư',
            'Thursday'  => 'Thứ Năm',
            'Friday'    => 'Thứ Sáu',
            'Saturday'  => 'Thứ Bảy',
            'Sunday'    => 'Chủ Nhật',
        ];

        $now = now()->locale('vi_VN');
        $dayName = $days[$now->englishDayOfWeek] ?? $now->englishDayOfWeek;
        $dateStr = $now->format('d/m/Y');

        return sprintf('Hôm nay: %s ngày %s', $dayName, $dateStr);
    }
}

if (!function_exists('format_vietnamese_datetime')) {
    /**
     * Hiển thị ngày giờ dạng "Thứ 2, 29/09/2025 15:15" bằng tiếng Việt.
     *
     * @param  string|\DateTimeInterface|null  $value
     * @param  bool  $showTime
     * @return string|null
     */
    function format_vietnamese_datetime($value, bool $showTime = true): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);
            $dt->locale('vi');

            $days = [
                0 => 'Chủ nhật',
                1 => 'Thứ hai',
                2 => 'Thứ ba',
                3 => 'Thứ tư',
                4 => 'Thứ năm',
                5 => 'Thứ sáu',
                6 => 'Thứ bảy',
            ];

            $thu = $days[$dt->dayOfWeek];
            $date = $dt->format('d/m/Y');
            $time = $dt->format('H:i');

            return $showTime
                ? "{$thu}, {$date} {$time}"
                : "{$thu}, {$date}";
        } catch (\Throwable $e) {
            return null;
        }
    }
}