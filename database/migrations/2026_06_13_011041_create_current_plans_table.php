<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('current_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("plan_id");
            $table->enum("status", ["pending", "running", "expired"])->default("pending");
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign("plan_id")->references("id")->on("plans")->cascadeOnDelete();

            $table->timestamps();
            $table->unique(["school_id", "plan_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_plans');
    }
};
