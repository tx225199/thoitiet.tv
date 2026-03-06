<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'logo'              => '/uploads/images/setting/logo.png',
            'favicon'           => '/uploads/images/setting/favicon.png',
            'title'             => 'Dự báo thời tiết 63 tỉnh thành Việt Nam – Chính xác theo giờ',
            'site_name'         => 'thoitiet.tv',
            'version'           => '1.0',
            'theme_color'       => '#1e62b5', // xanh thời tiết
            'google_analytics'  => '',
            'microsoft_clarity' => '',
            'mail'              => 'contact@thoitiet.tv',
            'description'       => 'Thoitiet.tv – Cập nhật dự báo thời tiết theo giờ, theo ngày cho 63 tỉnh thành Việt Nam. Xem nhiệt độ, cảm giác như, độ ẩm, gió, tầm nhìn, UV và cảnh báo thời tiết.',
            'introduce'         => "thoitiet.tv là trang tra cứu thời tiết nhanh và dễ dùng cho 63 tỉnh thành Việt Nam.\nBạn có thể xem dự báo theo giờ, theo ngày, chỉ số UV, gió, độ ẩm, tầm nhìn và các cảnh báo thời tiết.\nDữ liệu được cập nhật liên tục để bạn chủ động kế hoạch di chuyển và sinh hoạt.",
            'copyright'         => '© 2026 thoitiet.tv. All rights reserved.',
            'notification'      => '☁️ Xem dự báo thời tiết hôm nay & 7 ngày tới trên thoitiet.tv',
            'introduct_footer'  => 'Thông tin thời tiết mang tính tham khảo. Vui lòng theo dõi thêm cảnh báo chính thức từ cơ quan khí tượng khi có thời tiết nguy hiểm.',
            'custom_css'        => '',
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        });
    }
}