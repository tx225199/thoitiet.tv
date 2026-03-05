<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('article_genres')->truncate();
            DB::table('genres')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Seed lại
            $genres = [
                [
                    'slug' => 'tin-xo-so',
                    'name' => 'Tin Xổ Số',
                    'description' => 'Cập nhật tin tức xổ số mới nhất, kết quả, phân tích và dự đoán từ các nguồn uy tín.',
                    'meta_title' => 'Tin Xổ Số mới nhất hôm nay - Kết quả & dự đoán tại xosovn.net',
                    'meta_description' => 'Trang Tin Xổ Số tại xosovn.net cập nhật nhanh kết quả xổ số 3 miền, phân tích và dự đoán con số may mắn hàng ngày cho anh em yêu thích lô đề.',
                ],
                [
                    'slug' => 'quy-dinh-xo-so',
                    'name' => 'Quy định xổ số',
                    'description' => 'Tổng hợp các quy định, hướng dẫn và chính sách liên quan đến hoạt động xổ số kiến thiết.',
                    'meta_title' => 'Quy định Xổ Số - Hướng dẫn & chính sách mới nhất | xosovn.net',
                    'meta_description' => 'Tìm hiểu các quy định và chính sách mới nhất về hoạt động xổ số kiến thiết Việt Nam được cập nhật tại xosovn.net.',
                ],
                [
                    'slug' => 'tin-trung-thuong',
                    'name' => 'Tin trúng thưởng',
                    'description' => 'Những câu chuyện người trúng thưởng, bí quyết nhận giải và tin vui từ các đợt quay số.',
                    'meta_title' => 'Tin Trúng Thưởng mới nhất - Câu chuyện người trúng số | xosovn.net',
                    'meta_description' => 'Xem ngay những tin tức trúng thưởng mới nhất, câu chuyện may mắn và bí quyết nhận giải độc đắc được tổng hợp tại xosovn.net.',
                ],
                [
                    'slug' => 'loc-hom-nay',
                    'name' => 'Lộc hôm nay',
                    'description' => 'Chia sẻ con số may mắn, mẹo dự đoán và lộc tài mỗi ngày cho anh em đam mê xổ số.',
                    'meta_title' => 'Lộc Hôm Nay - Con số may mắn & mẹo dự đoán chuẩn | xosovn.net',
                    'meta_description' => 'Khám phá con số may mắn, mẹo soi cầu và lộc tài hôm nay dành cho người chơi xổ số tại xosovn.net.',
                ],
                [
                    'slug' => 'top-nha-cai',
                    'name' => 'Top nhà cái',
                    'description' => 'Danh sách và đánh giá các nhà cái uy tín, khuyến mãi và tỷ lệ thưởng hấp dẫn nhất hiện nay.',
                    'meta_title' => 'Top Nhà Cái Uy Tín 2025 - Đánh giá & khuyến mãi hấp dẫn | xosovn.net',
                    'meta_description' => 'Danh sách Top Nhà Cái uy tín, tỷ lệ trả thưởng cao và nhiều ưu đãi hấp dẫn nhất năm 2025 được cập nhật tại xosovn.net.',
                ],
            ];

            DB::table('genres')->insert($genres);

        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            throw $e;
        }
    }
}
