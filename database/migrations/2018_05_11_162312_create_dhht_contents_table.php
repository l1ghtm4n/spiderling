<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDhhtContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dhht_contents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->string('rating', 255)->nullable();
            $table->string('product_code', 255)->nullable();
            $table->string('product_brand', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->string('price', 255)->nullable();
            $table->text('sub_description')->nullable();
            $table->text('description')->nullable();
            $table->text('picture')->nullable();
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
        Schema::dropIfExists('dhht_contents');
    }
}
