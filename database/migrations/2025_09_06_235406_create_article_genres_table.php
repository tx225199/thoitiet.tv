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
        Schema::create('article_genres', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('genre_id');
            $table->primary(['article_id','genre_id']);
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_genres');
    }
};
