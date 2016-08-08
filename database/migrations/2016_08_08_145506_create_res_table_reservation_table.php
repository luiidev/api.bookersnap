<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTableReservationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_table_reservation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('num_people')->unsigned();
            $table->bigInteger('res_table_id')->unsigned();
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('res_reservation_id')->unsigned();
            $table->bigInteger('res_table_status_id')->unsigned();
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
        Schema::drop('res_table_reservation');
    }
}
