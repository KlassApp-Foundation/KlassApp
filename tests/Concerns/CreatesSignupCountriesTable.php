<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Register views read the countries table. Auth tests that render those
 * views do not migrate, so they create the table themselves.
 */
trait CreatesSignupCountriesTable
{
    protected function createSignupCountriesTable(): void
    {
        if (Schema::hasTable('countries')) {
            return;
        }

        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('iso_code')->nullable();
            $table->string('tel_prefix')->nullable();
            $table->boolean('status')->default(0);
            $table->integer('order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
