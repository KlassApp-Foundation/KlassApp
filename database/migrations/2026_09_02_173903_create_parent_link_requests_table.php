<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_link_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('school_id')->nullable()->index();
            $table->string('phone', 20);
            $table->string('parent_name', 150);
            $table->string('child_name', 150);
            $table->string('child_class', 50);
            $table->unsignedInteger('suggested_student_id')->nullable();
            $table->unsignedInteger('matched_student_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('flow_token', 100)->nullable();
            $table->json('candidate_student_ids')->nullable();
            $table->timestamps();

            $table->index(['phone', 'status']);
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_link_requests');
    }
};
