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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'no_of_students')) {
                $table->integer('no_of_students')->default(0)->after('no_of_members');
            }
            if (!Schema::hasColumn('plans', 'no_of_users')) {
                $table->integer('no_of_users')->default(0)->after('no_of_students');
            }
        });

        // Set sensible defaults based on plan name
        DB::table('plans')->where('name', 'freemium')->update([
            'no_of_students' => 100,
            'no_of_users'    => 10,
        ]);
        DB::table('plans')->where('name', 'growth')->update([
            'no_of_students' => 1000,
            'no_of_users'    => 50,
        ]);
        DB::table('plans')->where('name', 'premium')->update([
            'no_of_students' => 99999,
            'no_of_users'    => 999,
        ]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['no_of_students', 'no_of_users']);
        });
    }
};
