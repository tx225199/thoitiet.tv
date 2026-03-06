<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HotWeatherArticlesFullSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1) GENRE
        DB::table('genres')->updateOrInsert(
            ['slug' => 'thoi-tiet'],
            [
                'name'             => 'Thời tiết',
                'slug'             => 'thoi-tiet',
                'description'      => 'Tin tức, kiến thức và hướng dẫn liên quan đến thời tiết.',
                'meta_title'       => 'Chuyên mục Thời tiết',
                'meta_description' => 'Cập nhật tin tức và kiến thức thời tiết.',
                'meta_keywords'    => 'thời tiết, dự báo, mưa, nắng, uv, gió',
                'hidden'           => 0,
                'sort'             => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]
        );

        $genreId = DB::table('genres')->where('slug', 'thoi-tiet')->value('id');

        // 2) TAGS
        $tags = [
            ['name' => 'Dự báo',      'slug' => 'du-bao'],
            ['name' => 'Mưa',         'slug' => 'mua'],
            ['name' => 'Gió',         'slug' => 'gio'],
            ['name' => 'Độ ẩm',       'slug' => 'do-am'],
            ['name' => 'Chỉ số UV',   'slug' => 'uv'],
            ['name' => 'An toàn',     'slug' => 'an-toan'],
        ];

        foreach ($tags as $t) {
            DB::table('tags')->updateOrInsert(
                ['slug' => $t['slug']],
                [
                    'name'       => $t['name'],
                    'slug'       => $t['slug'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $tagIdBySlug = DB::table('tags')
            ->whereIn('slug', array_column($tags, 'slug'))
            ->pluck('id', 'slug')
            ->toArray();

        // 3) ARTICLES (4 bài)
        $items = [
            [
                'title'            => 'Dự báo thời tiết 7 ngày tới: cách đọc chính xác nhiệt độ và lượng mưa',
                'slug'             => 'du-bao-thoi-tiet-7-ngay-toi-cach-doc-chinh-xac',
                'excerpt'          => 'Hướng dẫn đọc dự báo 7 ngày: nhiệt độ, khả năng mưa, độ ẩm, gió và cảnh báo thời tiết.',
                'content'          => '<p>Hướng dẫn cách đọc dự báo thời tiết 7 ngày: nhiệt độ, lượng mưa, độ ẩm, gió, UV và cảnh báo.</p>',
                'thumbnail'        => '/uploads/images/seed/weather-forecast-7-days.jpg',
                'avatar'           => '/uploads/images/seed/weather-forecast-7-days.jpg',
                'meta_title'       => 'Dự báo thời tiết 7 ngày tới - cách đọc chuẩn',
                'meta_description' => 'Cách đọc dự báo 7 ngày: nhiệt độ, mưa, độ ẩm, gió, UV và cảnh báo thời tiết.',
                'meta_keywords'    => 'dự báo thời tiết, 7 ngày tới, nhiệt độ, mưa, độ ẩm, gió, uv',
                'tags'             => ['du-bao', 'mua', 'gio', 'do-am'],
            ],
            [
                'title'            => 'Thời tiết hôm nay: 5 chỉ số bạn nên xem trước khi ra đường',
                'slug'             => 'thoi-tiet-hom-nay-5-chi-so-nen-xem',
                'excerpt'          => 'Nhiệt độ cảm giác, độ ẩm, gió, tầm nhìn và UV là 5 chỉ số quan trọng nhất.',
                'content'          => '<p>Trước khi ra đường, hãy kiểm tra 5 chỉ số: nhiệt độ cảm giác, độ ẩm, gió, tầm nhìn và UV.</p>',
                'thumbnail'        => '/uploads/images/seed/weather-today-5-metrics.jpg',
                'avatar'           => '/uploads/images/seed/weather-today-5-metrics.jpg',
                'meta_title'       => 'Thời tiết hôm nay: 5 chỉ số quan trọng',
                'meta_description' => '5 chỉ số cần xem: cảm giác như, độ ẩm, gió, tầm nhìn, UV.',
                'meta_keywords'    => 'thời tiết hôm nay, độ ẩm, gió, tầm nhìn, uv',
                'tags'             => ['do-am', 'gio', 'uv'],
            ],
            [
                'title'            => 'Mưa dông và sấm sét: cách nhận biết sớm và phòng tránh an toàn',
                'slug'             => 'mua-dong-sam-set-nhan-biet-som-phong-tranh',
                'excerpt'          => 'Nhận biết dấu hiệu mưa dông và một số nguyên tắc an toàn khi có sấm sét.',
                'content'          => '<p>Tổng hợp dấu hiệu mưa dông, sấm sét và nguyên tắc an toàn cơ bản khi hoạt động ngoài trời.</p>',
                'thumbnail'        => '/uploads/images/seed/thunderstorm-safety.jpg',
                'avatar'           => '/uploads/images/seed/thunderstorm-safety.jpg',
                'meta_title'       => 'Mưa dông, sấm sét: nhận biết & phòng tránh',
                'meta_description' => 'Dấu hiệu mưa dông và nguyên tắc an toàn khi có sấm sét.',
                'meta_keywords'    => 'mưa dông, sấm sét, cảnh báo thời tiết, an toàn',
                'tags'             => ['mua', 'an-toan'],
            ],
            [
                'title'            => 'Chỉ số UV là gì? Mức UV bao nhiêu thì cần chống nắng?',
                'slug'             => 'chi-so-uv-la-gi-muc-uv-chong-nang',
                'excerpt'          => 'Giải thích chỉ số UV, mức độ nguy cơ và cách chống nắng theo từng ngưỡng UV.',
                'content'          => '<p>Giải thích chỉ số UV và gợi ý bảo vệ da/mắt khi ra ngoài theo từng mức UV.</p>',
                'thumbnail'        => '/uploads/images/seed/uv-index-guide.jpg',
                'avatar'           => '/uploads/images/seed/uv-index-guide.jpg',
                'meta_title'       => 'Chỉ số UV là gì? Hướng dẫn chống nắng',
                'meta_description' => 'Giải thích UV và cách chống nắng theo từng mức UV.',
                'meta_keywords'    => 'uv, chỉ số uv, chống nắng, thời tiết',
                'tags'             => ['uv', 'an-toan'],
            ],
        ];

        foreach ($items as $it) {
            DB::table('articles')->updateOrInsert(
                ['slug' => $it['slug']],
                [
                    'genre_id'         => $genreId, // có genre_id luôn
                    'title'            => $it['title'],
                    'slug'             => $it['slug'],
                    'excerpt'          => $it['excerpt'],
                    'content'          => $it['content'],
                    'type'             => 'text',
                    'avatar'           => $it['avatar'],
                    'thumbnail'        => $it['thumbnail'],
                    'meta_title'       => $it['meta_title'],
                    'meta_description' => $it['meta_description'],
                    'meta_keywords'    => $it['meta_keywords'],
                    'highlight'        => 1,
                    'hidden'           => 0,
                    'published_at'     => $now->copy()->subMinutes(5),
                    'url'              => null,
                    'copyright'        => null,
                    'copy_at'          => null,
                    'post_type'        => 'manual',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                    'created_by'       => 1,
                    'updated_by'       => 1,
                ]
            );

            $articleId = DB::table('articles')->where('slug', $it['slug'])->value('id');

            // 4) PIVOT article_genres (n-n)
            if ($genreId && $articleId) {
                DB::table('article_genres')->updateOrInsert(
                    ['article_id' => $articleId, 'genre_id' => $genreId],
                    ['article_id' => $articleId, 'genre_id' => $genreId]
                );
            }

            // 5) PIVOT article_tags
            if ($articleId && !empty($it['tags'])) {
                foreach ($it['tags'] as $tagSlug) {
                    $tagId = $tagIdBySlug[$tagSlug] ?? null;
                    if (!$tagId) continue;

                    DB::table('article_tags')->updateOrInsert(
                        ['article_id' => $articleId, 'tag_id' => $tagId],
                        ['article_id' => $articleId, 'tag_id' => $tagId]
                    );
                }
            }
        }
    }
}