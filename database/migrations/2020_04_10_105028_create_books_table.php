<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('school_id')->unsigned();
            $table->foreign('school_id')->references('id')->on('schools'); 
            $table->integer('academic_year_id')->unsigned();
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('books_category');
            $table->string('book_code');
            $table->string('title');
            $table->string('author')->nullable();
            $table->bigInteger('isbn_number')->nullable();
            $table->string('cover_image')->nullable();
            $table->integer('quantity')->nullable()->default(1);
            $table->timestamps();

            $table->unique(["school_id", "book_code"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books');
    }
}
