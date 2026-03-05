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
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên chuyên mục');
            $table->string('slug')->unique()->comment('Slug URL');
            $table->text('description')->nullable()->comment('Mô tả chuyên mục');
            $table->string('meta_title')->nullable()->comment('Thẻ meta title');
            $table->string('meta_description')->nullable()->comment('Thẻ meta description');
            $table->string('meta_keywords')->nullable()->comment('Thẻ meta keywords');
            $table->boolean('hidden')->default(false)->comment('Ẩn chuyên mục');
            $table->integer('sort')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
