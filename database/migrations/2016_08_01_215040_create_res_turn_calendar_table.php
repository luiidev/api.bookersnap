<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResTurnCalendarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_turn_calendar', function (Blueprint $table) {
            $table->bigInteger('res_turn_id')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->dateTime('date_add');
            $table->dateTime('date_upd');
            $table->bigInteger('user_add')->unsigned();
            $table->bigInteger('user_upd')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('res_turn_calendar');
    }
}
