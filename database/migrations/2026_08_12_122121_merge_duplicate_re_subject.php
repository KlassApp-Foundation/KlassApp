<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('marks')->where('subject_id', 248)->update(['subject_id' => 300]);
        DB::table('subjects')->where('id', 248)->delete();
    }

    public function down(): void {}
};
