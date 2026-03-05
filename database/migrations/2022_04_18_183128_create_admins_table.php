<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50)->nullable();
            $table->string('email',50)->unique();
            $table->string('phone',50)->nullable();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('admin');
            $table->string('avatar')->nullable();
            $table->string('remember_token',100)->nullable();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
}
