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
        Schema::create('advs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->string('position')->nullable();
            $table->string('link')->nullable();
            $table->string('other_link')->nullable();
            $table->text('script')->nullable();
            $table->string('des_media')->nullable();
            $table->string('mob_media')->nullable();
            $table->string('supplier')->nullable();
            $table->integer('sort')->default(1);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advs');
    }
};
