<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            // Per-school report card composition: which exam types appear on
            // the school's report cards and their weights where the school
            // uses weighting. Shape: { "<exam_type_id>": { "include": bool, "weight": number|null } }.
            $table->json('report_composition')->nullable()->after('exam_type_preferences');

            // Per-school curriculum profile key from config/curricula.php
            // (null = the configured default profile, the new curricula).
            $table->string('curriculum_profile')->nullable()->after('report_composition');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['report_composition', 'curriculum_profile']);
        });
    }
};
