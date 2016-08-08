<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTableBlockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_table_block', function (Blueprint $table) {
            $table->bigInteger('res_table_id')->unsigned();
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('res_block_id')->unsigned();
            $table->bigInteger('ms_microsite_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_table_block');
    }
}
