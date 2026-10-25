<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per roster file uploaded during onboarding (teachers, class
 * teachers, students). The file is processed by a queued job, so the wizard
 * can show progress instead of waiting on the request.
 *
 * `file_hash` makes a repeated upload of the same file return the first
 * result instead of creating the rows again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('onboarding_uploads')) {
            return;
        }

        Schema::create('onboarding_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('user_id');
            $table->string('kind', 20);
            $table->string('original_name')->nullable();
            $table->string('path');
            $table->char('file_hash', 64);
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->text('report')->nullable();
            $table->longText('rows')->nullable();
            $table->longText('skips')->nullable();
            $table->longText('notes')->nullable();
            $table->longText('missing_streams')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'kind', 'file_hash']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_uploads');
    }
};
