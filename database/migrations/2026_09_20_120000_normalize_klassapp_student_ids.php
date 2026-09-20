<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sequence = 1;

        DB::table('student_academics')
            ->whereNotNull('klassapp_student_id')
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->each(function (object $academic) use (&$sequence): void {
                $id = sprintf('KA%08d', $sequence++);

                DB::table('student_academics')
                    ->where('id', $academic->id)
                    ->update(['klassapp_student_id' => $id]);

                DB::table('users')
                    ->where('id', $academic->user_id)
                    ->update(['registration_number' => $id]);
            });

        DB::table('student_id_sequences')->update(['next_seq' => $sequence]);
    }

    public function down(): void
    {
        // The previous KLS values cannot be reconstructed after normalization.
    }
};