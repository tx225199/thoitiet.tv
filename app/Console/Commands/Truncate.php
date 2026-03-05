<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Truncate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truncate:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xoá trống dữ liệu các bảng phim';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [
            'jobs',
            'failed_jobs',
            'articles',
            'article_tags',
            // 'genres',
            'media'
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->info("\u2713 Truncated table: {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("\n\u2714\ufe0f Tất cả các bảng phim đã được xóa trống!");
    }
}
