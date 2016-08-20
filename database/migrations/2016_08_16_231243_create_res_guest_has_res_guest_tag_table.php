<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResGuestHasResGuestTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_guest_has_res_guest_tag', function (Blueprint $table) {
            $table->bigInteger('res_guest_id')->unsigned();
            $table->bigInteger('res_guest_tag_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_guest_has_res_guest_tag');
    }
}
