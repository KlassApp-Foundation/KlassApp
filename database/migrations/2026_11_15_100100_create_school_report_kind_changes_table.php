<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row each time a school changes its report type.
 * users.id is an integer increment; schools.id is a big integer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_report_kind_changes')) {
            return;
        }

        Schema::create('school_report_kind_changes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('from_kind', 32);
            $table->string('to_kind', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_report_kind_changes');
    }
};
