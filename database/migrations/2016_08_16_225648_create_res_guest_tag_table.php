<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResGuestTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_guest_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 32);
            $table->integer('status');
            $table->bigInteger('res_guest_tag_gategory_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_guest_tag');
    }
}
