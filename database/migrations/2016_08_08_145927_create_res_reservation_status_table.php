<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResReservationStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_reservation_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',64);
            $table->string('color',128)->nullable(); // es croquis
            $table->integer('status')->unsigned();
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned()->nullable()->default(NULL);
            $table->dateTime('date_add');
            $table->dateTime('date_upd')->nullable()->default(NULL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_reservation_status');
    }
}