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
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable()->comment('Tên hiển thị: p1, p2...');
            $table->string('ip', 100)->comment('IP:port');
            $table->string('username', 100)->nullable();
            $table->string('password', 100)->nullable();
            $table->text('rotate_url')->nullable()->comment('API để xoay IP');
            $table->boolean('active')->default(true)->comment('Có đang hoạt động không');
            $table->timestamp('last_used_at')->nullable()->comment('Thời điểm dùng gần nhất');
            $table->timestamp('last_rotated_at')->nullable()->comment('Thời điểm xoay IP gần nhất');
            $table->integer('rotate_cooldown')->default(60)->comment('Cooldown giữa 2 lần xoay IP (giây)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
