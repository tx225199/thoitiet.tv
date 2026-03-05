<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genre_id')->nullable()->comment('ID thể loại');
            $table->string('title')->comment('Tiêu đề bài viết');
            $table->string('slug')->unique()->comment('Slug URL');
            $table->text('excerpt')->nullable()->comment('Tóm tắt nội dung');
            $table->longText('content')->nullable()->comment('Nội dung bài viết');
            $table->string('type', 20)->default('text')->index(); // 'text' | 'video'
            $table->string('avatar')->nullable()->comment('Ảnh avatar');
            $table->string('thumbnail')->nullable()->comment('Ảnh thumbnail');
            $table->string('meta_title')->nullable()->comment('Thẻ meta title');
            $table->text('meta_description')->nullable()->comment('Thẻ meta description');
            $table->string('meta_keywords')->nullable()->comment('Thẻ meta keywords');
            $table->boolean('highlight')->default(false)->comment('Ghim nổi bật');
            $table->boolean('hidden')->default(false)->comment('Ẩn bài viết');
            $table->timestamp('published_at')->nullable()->comment('Thời gian đăng bài');
            $table->text('url')->nullable();
            $table->text('copyright')->nullable();
            $table->text('copy_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
