<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('teacher_designations')->nullable()->after('registration_role');
        });

        // Backfill teacher_designations from existing laratrust role assignments
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            $assignments = DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->where('role_user.user_type', 'App\Models\User')
                ->select('role_user.user_id', 'roles.name')
                ->get()
                ->groupBy('user_id');

            foreach ($assignments as $userId => $roles) {
                $designations = $roles->pluck('name')->unique()->values()->toArray();
                DB::table('users')
                    ->where('id', $userId)
                    ->update(['teacher_designations' => json_encode($designations)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('teacher_designations');
        });
    }
};
