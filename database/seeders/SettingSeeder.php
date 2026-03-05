<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'logo'              => '',
            'favicon'           => '',
            'title'             => 'Xổ Số VN – Kết Quả Xổ Số 3 Miền Nhanh & Chính Xác',
            'site_name'         => 'xosovn.net',
            'version'           => '1.0',
            'theme_color'       => '#d32f2f', // đỏ đậm kiểu xổ số
            'google_analytics'  => '',
            'microsoft_clarity' => '',
            'mail'              => 'contact@xosovn.net',
            'description'       => 'Xổ Số VN (xosovn.net) – Cập nhật kết quả xổ số 3 miền Bắc, Trung, Nam nhanh & chính xác. Tường thuật trực tiếp KQXS hôm nay, thống kê, soi cầu, đầu đuôi, lô tô, lịch mở thưởng hàng ngày.',
            'introduce'         => "xosovn.net là cổng thông tin xổ số trực tuyến uy tín, cập nhật kết quả 3 miền Bắc – Trung – Nam liên tục 24/7.
Người dùng có thể tra cứu nhanh kết quả theo tỉnh, theo ngày và theo kỳ quay thưởng.
Ngoài ra còn có thống kê lô tô, soi cầu, đầu – đuôi, cầu bạch thủ, giúp bạn tham khảo hiệu quả hơn trước khi dự đoán.",
            'copyright'         => '© 2025 xosovn.net. All rights reserved.',
            'notification'      => '🎯 KQXS hôm nay đã có! Cập nhật nhanh kết quả tại xosovn.net',
            'introduct_footer'  => 'xosovn.net cung cấp thông tin tham khảo từ các nguồn chính thống. Vui lòng đối chiếu với kết quả mở thưởng chính thức của các công ty xổ số kiến thiết.',
            'custom_css'        => '',
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $key => $value) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        });
    }
}
