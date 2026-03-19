<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostalRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postal_record', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('school_id')->unsigned();
            $table->foreign('school_id')->references('id')->on('schools');
            $table->integer('academic_year_id')->unsigned();
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->string('type');
            $table->string('post_type');
            $table->string('reference_number')->nullable();
            $table->string('confidential');
            $table->string('sender_title')->nullable();
            $table->longText('sender_address')->nullable();
            $table->string('receiver_title')->nullable();
            $table->string('receiver_address')->nullable();
            $table->date('postal_date');
            $table->longText('description');
            $table->string('attachment');
            $table->string('entry_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('postal_record');
    }
}
