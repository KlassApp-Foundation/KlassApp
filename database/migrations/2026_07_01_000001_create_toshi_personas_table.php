<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toshi_personas', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('summary')->nullable()->comment('One-paragraph persona description of user style, preferences, vibe');
            $table->json('traits')->nullable()->comment('Array of trait keywords like concise, detail-oriented, etc.');
            $table->integer('interaction_count')->default(0)->comment('Total LLM interactions used for persona learning');
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toshi_personas');
    }
};
