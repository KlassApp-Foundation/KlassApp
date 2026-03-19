<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('school_id')->unsigned();
            $table->foreign('school_id')->references('id')->on('schools');
            $table->integer('academic_year_id')->unsigned();
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->string('calling_purpose')->nullable();
            $table->string('call_type');
            $table->text('name');
            $table->string('incoming_number')->nullable();
            $table->string('outgoing_number')->nullable();
            $table->date('call_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('duration')->nullable();
            $table->longText('description')->nullable();
            $table->text('entry_by');
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
        Schema::dropIfExists('call_log');
    }
}
