<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row each time a school saves, resets or loads a preset for its progressive report rules
 * (grade bands, division ranges, ranking). The rules screen shows the latest row as
 * "last changed by". users.id is an integer increment; schools.id is a big integer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('progressive_rules_changes')) {
            return;
        }

        Schema::create('progressive_rules_changes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('standard_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('summary', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'standard_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progressive_rules_changes');
    }
};
