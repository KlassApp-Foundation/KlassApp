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
        //
        if(!Schema::hasTable("remarks")){
            Schema::create("remarks", function (Blueprint $table){
            $table->id();
            $table->string("remark");
             $table->unsignedBigInteger('school_id'); 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->timestamps();
        });
        }
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists("remarks");
    }
};
