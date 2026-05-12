<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('school_id')->unsigned();
            $table->foreign('school_id')->references('id')->on('schools');
            $table->string('name');
            $table->boolean('status')->default('1');
            $table->timestamps();

            $table->unsignedInteger('next_section_id')
                  ->nullable();

            $table->foreign('next_section_id')
                  ->references('id')
                  ->on('sections')
                  ->nullOnDelete();

            $table->softDeletes();
            $table->unique(["school_id", "name"]);
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sections');
    }
}
