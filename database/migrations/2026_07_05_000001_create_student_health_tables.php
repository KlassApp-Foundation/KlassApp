<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-to-one health profile per student
        Schema::create('student_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('user_id');
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('blood_type')->nullable();
            $table->text('medical_notes')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['school_id', 'user_id']);
        });

        // Immunization records (multiple per student)
        Schema::create('student_immunizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('user_id');
            $table->string('vaccine_name');
            $table->date('administered_date');
            $table->string('administered_by')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['school_id', 'user_id']);
        });

        // Health incident log (multiple per student)
        Schema::create('student_health_incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('user_id');
            $table->date('incident_date');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->unsignedInteger('recorded_by'); // staff user_id
            $table->string('severity')->nullable(); // minor, moderate, serious
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->index(['school_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_incidents');
        Schema::dropIfExists('student_immunizations');
        Schema::dropIfExists('student_health_profiles');
    }
};
