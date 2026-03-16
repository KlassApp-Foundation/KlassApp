<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubmittedOnToStudentHomeworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_homework', function (Blueprint $table) {
            //
            $table->date('submitted_on')->nullable()->after('attachment');
            $table->integer('checked_by')->unsigned()->nullable()->after('submitted_on');
            $table->foreign('checked_by')->references('id')->on('users');
            $table->date('checked_on')->nullable()->after('checked_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_homework', function (Blueprint $table) {
            //
        });
    }
}