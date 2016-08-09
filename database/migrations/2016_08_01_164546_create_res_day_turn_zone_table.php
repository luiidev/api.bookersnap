<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResDayTurnZoneTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('res_day_turn_zone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->Integer('day');
            $table->dateTime('date_ini');
            $table->dateTime('date_end')->nullable();
            $table->bigInteger('res_turn_id')->unsigned();
            $table->bigInteger('res_zone_id')->unsigned();
            $table->bigInteger('res_type_turn_id')->unsigned();            
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
        Schema::drop('res_day_turn_zone');
    }
}
