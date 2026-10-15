<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safe to run twice.
        if (Schema::hasTable('early_access_requests')) {
            return;
        }

        Schema::create('early_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('contact_name');
            $table->string('contact_role', 120);
            $table->string('email');
            $table->string('phone', 50);
            $table->string('country', 120);
            $table->unsignedInteger('approx_students');
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index('created_at');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_access_requests');
    }
};
