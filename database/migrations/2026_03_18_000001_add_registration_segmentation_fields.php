<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrationSegmentationFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('registration_country')->nullable()->after('phone');
            $table->string('student_size')->nullable()->after('registration_country');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_role')->nullable()->after('mobile_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registration_role');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['registration_country', 'student_size']);
        });
    }
}
