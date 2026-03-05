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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('image'); // dự phòng mở rộng
            $table->string('original_url');               // link gốc trong bài
            $table->string('stored_path')->nullable();    // images/posters/xxx.webp (relative)
            $table->string('filename')->nullable();       // tên file webp cuối
            $table->unsignedInteger('position')->default(0); // thứ tự trong bài
            $table->json('meta')->nullable();             // width/height/mime/hash...
            $table->timestamps();

            $table->unique(['article_id', 'original_url']);
            $table->index('original_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
