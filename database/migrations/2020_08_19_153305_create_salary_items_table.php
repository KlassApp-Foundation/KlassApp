<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('salary_id')->unsigned();
            $table->foreign('salary_id')->references('id')->on('salaries')->onDelete('cascade');
            $table->integer('template_item_id')->unsigned();
            $table->foreign('template_item_id')->references('id')->on('template_items');
            $table->string('amount');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_items');
    }
}
