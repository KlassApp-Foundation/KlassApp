<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            // users.id is INT UNSIGNED (legacy increments), so the FK column must
            // match it exactly: MySQL rejects a BIGINT -> INT foreign key (3780).
            $table->unsignedInteger('user_id');
            $table->string('key', 191);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
