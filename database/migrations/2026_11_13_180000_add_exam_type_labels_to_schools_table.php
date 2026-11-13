<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school can rename the exam sets it is offered. The shared catalog row
 * stays as it was. Reports read this label when it is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('schools', 'exam_type_labels')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->json('exam_type_labels')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('schools', 'exam_type_labels')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('exam_type_labels');
        });
    }
};
