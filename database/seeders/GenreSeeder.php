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

            $genres = [
                [
                    'slug' => 'tin-tong-hop',
                    'name' => 'Tin tổng hợp',
                    'description' => 'Tổng hợp các tin tức nổi bật trong ngày về đời sống, xã hội, xu hướng và thông tin đáng chú ý.',
                    'meta_title' => 'Tin tổng hợp mới nhất hôm nay',
                    'meta_description' => 'Cập nhật nhanh các tin tổng hợp mới nhất hôm nay về đời sống, xã hội và các thông tin đáng chú ý tại thoitiet.tv.',
                ],
                [
                    'slug' => 'tin-thoi-tiet',
                    'name' => 'Tin thời tiết',
                    'description' => 'Cập nhật nhanh tình hình thời tiết, dự báo mới nhất, cảnh báo mưa bão và thông tin khí hậu tại các khu vực.',
                    'meta_title' => 'Tin thời tiết mới nhất hôm nay - Dự báo, mưa bão',
                    'meta_description' => 'Theo dõi tin thời tiết mới nhất hôm nay, dự báo thời tiết, cảnh báo mưa bão và biến động khí hậu trên toàn quốc tại thoitiet.tv.',
                ],
            ];

            DB::table('genres')->insert($genres);
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            throw $e;
        }
    }
}