<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premium_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('template_id')->default(1);          // 1–5
            $table->boolean('is_published')->default(false);
            $table->string('hero_image')->nullable();
            $table->json('custom_colors')->nullable();               // per-template color overrides
            $table->json('custom_content')->nullable();              // sections, headlines, body text
            $table->string('seo_title', 120)->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_pages');
    }
};
