<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIvivuContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivivu_contents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('lat_lon', 255)->nullable();
            $table->integer('rate')->nullable();
            $table->integer('point')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('web', 255)->nullable();
            $table->string('num_of_room')->nullable();
            $table->string('price')->nullable();
            $table->string('price_min', 255)->nullable();
            $table->string('price_max', 255)->nullable();
            $table->string('thumb', 255)->nullable();
            $table->text('picture')->nullable();
            $table->text('sub_description')->nullable();
            $table->text('description')->nullable();
            $table->integer('type')->nullable();
            $table->text('location_description')->nullable();
            $table->string('ean_code', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ivivu_contents');
    }
}
