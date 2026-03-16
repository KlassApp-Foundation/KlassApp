<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('usergroup_id')->unsigned();
            $table->foreign('usergroup_id')->references('id')->on('usergroups');
            $table->bigInteger('school_id')->unsigned()->nullable();
            $table->foreign('school_id')->references('id')->on('schools');
            $table->integer('ref_id')->unsigned()->nullable();
            $table->foreign('ref_id')->references('id')->on('users'); 
            $table->string('name')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            //$table->string('mobile_no')->unique()->nullable();
            $table->string('mobile_no')->nullable();//changed for demo
            $table->string('registration_number')->nullable();//changed for demo
            $table->string('password');
            $table->enum('status',['active','inactive','exit'])->default('active');
            $table->string('email_verification_code')->nullable();
            $table->boolean('email_verified')->default('0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile_verification_code')->nullable();
            $table->boolean('mobile_verified')->default('0');
            $table->timestamp('mobile_verified_at')->nullable();
            $table->boolean('is_reset')->default('0');
            $table->text('platform_token')->nullable();
            $table->text('device_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
